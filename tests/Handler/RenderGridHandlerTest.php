<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Handler;

use Quorae\GridBundle\Definition\GridDefinitionResolver;
use Quorae\GridBundle\Dto\GridView;
use Quorae\GridBundle\Dto\SortOrder;
use Quorae\GridBundle\Exception\GridNotFoundException;
use Quorae\GridBundle\Exception\MissingDataSourceException;
use Quorae\GridBundle\Handler\FilterHydrator;
use Quorae\GridBundle\Handler\PageParser;
use Quorae\GridBundle\Handler\RenderGridHandler;
use Quorae\GridBundle\Handler\ScalarCoercer;
use Quorae\GridBundle\Registry\GridRegistry;
use Quorae\GridBundle\Tests\Fixtures\ClasseChoicesProvider;
use Quorae\GridBundle\Tests\Fixtures\CompleteGrid;
use Quorae\GridBundle\Tests\Fixtures\DummyFilter;
use Quorae\GridBundle\Tests\Fixtures\InMemoryDataSource;
use Quorae\GridBundle\Tests\Fixtures\MinimalGrid;
use Quorae\GridBundle\Tests\Fixtures\OffsetPaginatedGrid;
use Quorae\GridBundle\Tests\Fixtures\PaginatedDataSource;
use Quorae\GridBundle\Tests\Fixtures\ProviderBackedFilterGrid;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

final class RenderGridHandlerTest extends TestCase
{
    public function testReturnsGridViewWithHydratedFilterAndResponse(): void
    {
        $row = new \stdClass();
        $row->code = '411';
        $dataSource = new InMemoryDataSource([$row]);
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', [
            'q' => '411',
            'criteria' => ['classe' => '4'],
            'p' => '1',
        ]);

        $view = $handler->handle('fixture_complete', $request);

        self::assertInstanceOf(GridView::class, $view);
        self::assertSame('fixture_complete', $view->definition->name);
        self::assertSame('grid-fixture_complete', $view->frameId);
        self::assertCount(1, $view->response->rows);
        self::assertSame(1, $view->response->page);

        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertSame('411', $view->filter->q);
        self::assertSame(4, $view->filter->classe);
        self::assertFalse($view->filter->revisedOnly);
    }

    public function testExtraContextTakesPrecedenceOverQueryString(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', [
            'criteria' => ['classe' => '4'],
        ]);

        $view = $handler->handle('fixture_complete', $request, ['classe' => 7]);
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertSame(7, $view->filter->classe);
    }

    public function testHydratesBooleanFilterFromTruthyScalar(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', [
            'criteria' => ['revisedOnly' => '1'],
        ]);

        $view = $handler->handle('fixture_complete', $request);
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertTrue($view->filter->revisedOnly);
    }

    public function testHydratesBooleanFilterFromFalseyScalar(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', [
            'criteria' => ['revisedOnly' => '0'],
        ]);

        $view = $handler->handle('fixture_complete', $request);
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertFalse($view->filter->revisedOnly);
    }

    public function testFallsBackToPageOneWhenPageIsAbsentOrInvalid(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $view = $handler->handle('fixture_complete', Request::create('/'));
        self::assertSame(1, $view->response->page);

        $view2 = $handler->handle('fixture_complete', Request::create('/', 'GET', ['p' => '0']));
        self::assertSame(1, $view2->response->page);

        $view3 = $handler->handle('fixture_complete', Request::create('/', 'GET', ['p' => 'abc']));
        self::assertSame(1, $view3->response->page);
    }

    public function testParsesSortOrderFromQueryString(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', ['sort' => 'code:desc']);
        $view = $handler->handle('fixture_complete', $request);

        self::assertInstanceOf(SortOrder::class, $view->sort);
        self::assertSame('code', $view->sort->column);
        self::assertSame('desc', $view->sort->direction);
    }

    public function testFallsBackToDefaultSortOnUnknownOrUnsortableColumn(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        // `label` is declared but not sortable → fall back to defaultSort.
        $request = Request::create('/', 'GET', ['sort' => 'label:asc']);
        $view = $handler->handle('fixture_complete', $request);
        self::assertNotNull($view->sort);
        self::assertSame('code', $view->sort->column);
        self::assertSame('asc', $view->sort->direction);

        // Unknown column.
        $request2 = Request::create('/', 'GET', ['sort' => 'unknown:asc']);
        $view2 = $handler->handle('fixture_complete', $request2);
        self::assertNotNull($view2->sort);
        self::assertSame('code', $view2->sort->column);

        // Malformed value.
        $request3 = Request::create('/', 'GET', ['sort' => 'code']);
        $view3 = $handler->handle('fixture_complete', $request3);
        self::assertNotNull($view3->sort);
        self::assertSame('code', $view3->sort->column);
    }

    public function testInjectsDefaultSortWhenQueryStringIsSilent(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET');

        $view = $handler->handle('fixture_complete', $request);

        self::assertNotNull($view->sort);
        self::assertSame('code', $view->sort->column);
        self::assertSame('asc', $view->sort->direction);
    }

    public function testFallsBackToDefaultSortWhenDirectionIsInvalid(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', ['sort' => 'code:sideways']);

        $view = $handler->handle('fixture_complete', $request);

        self::assertNotNull($view->sort);
        self::assertSame('code', $view->sort->column);
        self::assertSame('asc', $view->sort->direction);
    }

    public function testHonoursUserProvidedSortForDeclaredColumn(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $request = Request::create('/', 'GET', ['sort' => 'code:desc']);

        $view = $handler->handle('fixture_complete', $request);

        self::assertNotNull($view->sort);
        self::assertSame('code', $view->sort->column);
        self::assertSame('desc', $view->sort->direction);
    }

    public function testThrowsWhenGridIsUnknown(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $this->expectException(GridNotFoundException::class);
        $handler->handle('ghost', Request::create('/'));
    }

    public function testThrowsWhenDataSourceIsNotRegistered(): void
    {
        $registry = new GridRegistry(
            ['fixture_complete' => CompleteGrid::class],
            new GridDefinitionResolver(),
        );
        $handler = new RenderGridHandler(
            registry: $registry,
            dataSources: new ServiceLocator([]),
            choicesProviders: new ServiceLocator([]),
            filterHydrator: new FilterHydrator(new ScalarCoercer()),
            pageParser: new PageParser(),
        );

        $this->expectException(MissingDataSourceException::class);
        $handler->handle('fixture_complete', Request::create('/'));
    }

    public function testCoercesInvalidCriteriaTypesToDefaults(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        // `classe` expected int, receive an array — resolver drops it.
        $request = Request::create('/', 'GET', [
            'criteria' => ['classe' => ['malicious']],
        ]);

        $view = $handler->handle('fixture_complete', $request);
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertNull($view->filter->classe);
    }

    public function testRejectsCriteriaForUndeclaredProperty(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        // `ghostField` is not declared on the grid ; it is silently ignored.
        $request = Request::create('/', 'GET', [
            'criteria' => ['ghostField' => 'whatever'],
        ]);

        $view = $handler->handle('fixture_complete', $request);

        // No exception, filter built with defaults only.
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertNull($view->filter->q);
    }

    public function testPassesExtraContextClientIdToFilter(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $view = $handler->handle('fixture_complete', Request::create('/'), ['clientId' => 42]);
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertSame(42, $view->filter->clientId);
    }

    public function testPageNumberPropagatesToDataSource(): void
    {
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $handler->handle('fixture_complete', Request::create('/', 'GET', ['p' => '3']));

        self::assertNotNull($dataSource->capturedPage);
        self::assertSame(3, $dataSource->capturedPage->number);
        self::assertSame(25, $dataSource->capturedPage->limit);
    }

    public function testWorksForMinimalGridWithoutFiltersOrSearch(): void
    {
        $dataSource = new InMemoryDataSource();
        $registry = new GridRegistry(
            ['fixture_minimal' => MinimalGrid::class],
            new GridDefinitionResolver(),
        );
        $handler = new RenderGridHandler(
            registry: $registry,
            dataSources: $this->dataSourceLocator($dataSource),
            choicesProviders: new ServiceLocator([]),
            filterHydrator: new FilterHydrator(new ScalarCoercer()),
            pageParser: new PageParser(),
        );

        $view = $handler->handle('fixture_minimal', Request::create('/'));
        self::assertInstanceOf(DummyFilter::class, $view->filter);
        self::assertSame('grid-fixture_minimal', $view->frameId);
    }

    public function testResolvesChoicesProviderAtRuntimeWithoutMutatingRegistryDefinition(): void
    {
        $dataSource = new InMemoryDataSource();
        $provider = new ClasseChoicesProvider();
        $registry = new GridRegistry(
            ['fixture_provider_backed' => ProviderBackedFilterGrid::class],
            new GridDefinitionResolver(),
        );
        $handler = new RenderGridHandler(
            registry: $registry,
            dataSources: $this->dataSourceLocator($dataSource),
            choicesProviders: new ServiceLocator([
                ClasseChoicesProvider::class => static fn (): ClasseChoicesProvider => $provider,
            ]),
            filterHydrator: new FilterHydrator(new ScalarCoercer()),
            pageParser: new PageParser(),
        );

        $view = $handler->handle(
            'fixture_provider_backed',
            Request::create('/', 'GET', ['criteria' => ['classe' => '4']]),
            ['clientId' => 42],
        );

        self::assertSame(
            ['4' => 'Clients', '6' => 'Fournisseurs'],
            $view->definition->filters[0]->choices,
        );
        self::assertInstanceOf(DummyFilter::class, $provider->capturedFilter);
        self::assertSame(4, $provider->capturedFilter->classe);
        self::assertSame(42, $provider->capturedFilter->clientId);
        self::assertSame(['clientId' => 42], $provider->capturedExtraContext);
        self::assertSame([], $registry->get('fixture_provider_backed')->filters[0]->choices);
    }

    public function testClampsOutOfRangePageToLastPageForOffsetGrid(): void
    {
        // 25 rows, perPage 10 → 3 pages. Request page 99999.
        $dataSource = new PaginatedDataSource(totalRows: 25, perPage: 10);
        $handler = $this->handlerForOffsetGrid($dataSource);

        $view = $handler->handle('fixture_offset_paginated', Request::create('/', 'GET', ['p' => '99999']));

        self::assertSame(3, $view->response->page);
        self::assertSame(3, $view->response->totalPages);
        // Last page holds the 5 trailing rows (21..25).
        self::assertCount(5, $view->response->rows);
        self::assertSame('21', $view->response->rows[0]->code);
        self::assertFalse($view->response->hasNext);
        self::assertTrue($view->response->hasPrev);
        // The handler must re-fetch at the clamped page so the paginator + rows agree.
        self::assertSame([99999, 3], $dataSource->fetchedPages);
    }

    public function testInRangePageIsNotReclampedForOffsetGrid(): void
    {
        $dataSource = new PaginatedDataSource(totalRows: 25, perPage: 10);
        $handler = $this->handlerForOffsetGrid($dataSource);

        $view = $handler->handle('fixture_offset_paginated', Request::create('/', 'GET', ['p' => '2']));

        self::assertSame(2, $view->response->page);
        self::assertCount(10, $view->response->rows);
        self::assertSame('11', $view->response->rows[0]->code);
        // No re-fetch — a single fetch at the requested page.
        self::assertSame([2], $dataSource->fetchedPages);
    }

    public function testLastPageBoundaryIsNotReclamped(): void
    {
        $dataSource = new PaginatedDataSource(totalRows: 30, perPage: 10);
        $handler = $this->handlerForOffsetGrid($dataSource);

        $view = $handler->handle('fixture_offset_paginated', Request::create('/', 'GET', ['p' => '3']));

        self::assertSame(3, $view->response->page);
        self::assertSame([3], $dataSource->fetchedPages);
    }

    public function testPrevNextGridWithoutTotalPagesIsNeverReclamped(): void
    {
        // InMemoryDataSource returns totalPages = null → clamp must not engage.
        $dataSource = new InMemoryDataSource();
        $handler = $this->handlerForCompleteGrid($dataSource);

        $view = $handler->handle('fixture_complete', Request::create('/', 'GET', ['p' => '99999']));

        self::assertSame(99999, $view->response->page);
        self::assertNotNull($dataSource->capturedPage);
        self::assertSame(99999, $dataSource->capturedPage->number);
    }

    private function handlerForOffsetGrid(PaginatedDataSource $dataSource): RenderGridHandler
    {
        $registry = new GridRegistry(
            ['fixture_offset_paginated' => OffsetPaginatedGrid::class],
            new GridDefinitionResolver(),
        );

        return new RenderGridHandler(
            registry: $registry,
            dataSources: new ServiceLocator([
                PaginatedDataSource::class => static fn (): PaginatedDataSource => $dataSource,
            ]),
            choicesProviders: new ServiceLocator([]),
            filterHydrator: new FilterHydrator(new ScalarCoercer()),
            pageParser: new PageParser(),
        );
    }

    private function handlerForCompleteGrid(InMemoryDataSource $dataSource): RenderGridHandler
    {
        $registry = new GridRegistry(
            ['fixture_complete' => CompleteGrid::class],
            new GridDefinitionResolver(),
        );

        return new RenderGridHandler(
            registry: $registry,
            dataSources: $this->dataSourceLocator($dataSource),
            choicesProviders: new ServiceLocator([]),
            filterHydrator: new FilterHydrator(new ScalarCoercer()),
            pageParser: new PageParser(),
        );
    }

    private function dataSourceLocator(InMemoryDataSource $dataSource): ContainerInterface
    {
        return new ServiceLocator([
            InMemoryDataSource::class => static fn (): InMemoryDataSource => $dataSource,
        ]);
    }
}
