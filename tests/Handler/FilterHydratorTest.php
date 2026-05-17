<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Handler;

use Quorae\GridBundle\Definition\FilterDefinition;
use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Definition\SearchDefinition;
use Quorae\GridBundle\Enum\FilterType;
use Quorae\GridBundle\Enum\Pagination;
use Quorae\GridBundle\Enum\SearchMode;
use Quorae\GridBundle\Handler\FilterHydrator;
use Quorae\GridBundle\Handler\ScalarCoercer;
use Quorae\GridBundle\Tests\Fixtures\DummyFilter;
use Quorae\GridBundle\Tests\Fixtures\DummyStatus;
use Quorae\GridBundle\Tests\Fixtures\InMemoryDataSource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FilterHydratorTest extends TestCase
{
    private FilterHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new FilterHydrator(new ScalarCoercer());
    }

    public function testHydratesSearchFieldFromQ(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/', 'GET', ['q' => 'test']);

        $filter = $this->hydrator->hydrate($definition, $request);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertSame('test', $filter->q);
    }

    public function testHydratesCriteriaWithIntCoercion(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/', 'GET', ['criteria' => ['classe' => '4']]);

        $filter = $this->hydrator->hydrate($definition, $request);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertSame(4, $filter->classe);
    }

    public function testHydratesBooleanFromTruthyString(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/', 'GET', ['criteria' => ['revisedOnly' => '1']]);

        $filter = $this->hydrator->hydrate($definition, $request);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertTrue($filter->revisedOnly);
    }

    public function testHydratesEnumFromStringValue(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/', 'GET', ['criteria' => ['status' => 'open']]);

        $filter = $this->hydrator->hydrate($definition, $request);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertSame(DummyStatus::Open, $filter->status);
    }

    public function testExtraContextOverridesCriteria(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/', 'GET', ['criteria' => ['classe' => '4']]);

        $filter = $this->hydrator->hydrate($definition, $request, ['classe' => 7]);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertSame(7, $filter->classe);
    }

    public function testFallsBackToDefaultsForInvalidValues(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/', 'GET', ['criteria' => ['classe' => ['malicious']]]);

        $filter = $this->hydrator->hydrate($definition, $request);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertNull($filter->classe);
    }

    public function testHandlesStdClassFilter(): void
    {
        $definition = new GridDefinition(
            name: 'test',
            dataSource: InMemoryDataSource::class,
            filterClass: \stdClass::class,
            pagination: Pagination::PrevNext,
            perPage: 25,
            interactive: false,
            emptyMessage: '',
            renderRow: null,
            columns: [],
            filters: [],
            search: null,
            rowSignatures: [],
        );

        $request = Request::create('/', 'GET', ['q' => 'hello', 'criteria' => ['foo' => 'bar']]);

        $filter = $this->hydrator->hydrate($definition, $request);

        self::assertInstanceOf(\stdClass::class, $filter);
        self::assertSame('hello', $filter->q);
        self::assertSame('bar', $filter->foo);
    }

    public function testExtraContextClientIdPassedToFilter(): void
    {
        $definition = $this->buildDefinition();
        $request = Request::create('/');

        $filter = $this->hydrator->hydrate($definition, $request, ['clientId' => 42]);

        self::assertInstanceOf(DummyFilter::class, $filter);
        self::assertSame(42, $filter->clientId);
    }

    private function buildDefinition(): GridDefinition
    {
        return new GridDefinition(
            name: 'test',
            dataSource: InMemoryDataSource::class,
            filterClass: DummyFilter::class,
            pagination: Pagination::PrevNext,
            perPage: 25,
            interactive: false,
            emptyMessage: '',
            renderRow: null,
            columns: [],
            filters: [
                new FilterDefinition(
                    propertyName: 'classe',
                    type: FilterType::Select,
                    label: 'Classe',
                    choices: [],
                    choicesProvider: null,
                ),
            ],
            search: new SearchDefinition(
                propertyName: 'q',
                fields: [],
                placeholder: 'Search',
                mode: SearchMode::Contains,
                debounceMs: 300,
            ),
            rowSignatures: [],
        );
    }
}
