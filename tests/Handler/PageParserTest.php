<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Handler;

use Quorae\GridBundle\Definition\ColumnDefinition;
use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Dto\SortOrder;
use Quorae\GridBundle\Enum\Formatter;
use Quorae\GridBundle\Enum\Pagination;
use Quorae\GridBundle\Handler\PageParser;
use Quorae\GridBundle\Tests\Fixtures\InMemoryDataSource;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class PageParserTest extends TestCase
{
    private PageParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PageParser();
    }

    public function testDefaultsToPageOneWhenNoPageParam(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/'));

        self::assertSame(1, $page->number);
    }

    public function testPageParamConstant(): void
    {
        self::assertSame('p', PageParser::PAGE_PARAM);
    }

    public function testParsesPageNumberFromQueryString(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['p' => '3']));

        self::assertSame(3, $page->number);
    }

    public function testClampsNegativePageToOne(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['p' => '-5']));

        self::assertSame(1, $page->number);
    }

    public function testClampsZeroPageToOne(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['p' => '0']));

        self::assertSame(1, $page->number);
    }

    public function testIgnoresNonIntegerPageValue(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['p' => 'abc']));

        self::assertSame(1, $page->number);
    }

    public function testClampsPageAboveMaxToMax(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['p' => '2000000']));

        self::assertSame(1_000_000, $page->number);
    }

    public function testIgnoresLegacyPageParam(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['page' => '5']));

        self::assertSame(1, $page->number);
    }

    public function testParsesSortFromQueryString(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['sort' => 'code:desc']));

        self::assertInstanceOf(SortOrder::class, $page->sort);
        self::assertSame('code', $page->sort->column);
        self::assertSame('desc', $page->sort->direction);
    }

    public function testFallsBackToDefaultSortWhenSortIsMalformed(): void
    {
        $definition = $this->buildDefinition();
        $page = $this->parser->parse($definition, Request::create('/', 'GET', ['sort' => 'code']));

        self::assertNotNull($page->sort);
        self::assertSame('code', $page->sort->column);
        self::assertSame('asc', $page->sort->direction);
    }

    public function testFallsBackToDefaultSortForUnsortableColumn(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['sort' => 'label:asc']));

        self::assertNotNull($page->sort);
        self::assertSame('code', $page->sort->column);
        self::assertSame('asc', $page->sort->direction);
    }

    public function testFallsBackToDefaultSortForInvalidDirection(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/', 'GET', ['sort' => 'code:sideways']));

        self::assertNotNull($page->sort);
        self::assertSame('code', $page->sort->column);
        self::assertSame('asc', $page->sort->direction);
    }

    public function testRejectsSortByPropertyNameWhenSortableKeyDiffers(): void
    {
        $definition = new GridDefinition(
            name: 'test_alias',
            dataSource: InMemoryDataSource::class,
            filterClass: \stdClass::class,
            pagination: Pagination::PrevNext,
            perPage: 25,
            interactive: false,
            emptyMessage: '',
            renderRow: null,
            columns: [
                new ColumnDefinition(
                    propertyName: 'accountNumber',
                    label: 'Numéro',
                    class: null,
                    formatter: Formatter::Plain,
                    template: null,
                    sortable: 'a.code',
                    hideOnMobile: false,
                ),
            ],
            filters: [],
            search: null,
            rowSignatures: [],
            defaultSort: new SortOrder(column: 'a.code', direction: 'asc'),
        );

        $page = $this->parser->parse($definition, Request::create('/', 'GET', ['sort' => 'accountNumber:desc']));

        self::assertNotNull($page->sort);
        self::assertSame('a.code', $page->sort->column);
        self::assertSame('asc', $page->sort->direction);
    }

    public function testAcceptsSortBySortableKey(): void
    {
        $definition = new GridDefinition(
            name: 'test_alias',
            dataSource: InMemoryDataSource::class,
            filterClass: \stdClass::class,
            pagination: Pagination::PrevNext,
            perPage: 25,
            interactive: false,
            emptyMessage: '',
            renderRow: null,
            columns: [
                new ColumnDefinition(
                    propertyName: 'accountNumber',
                    label: 'Numéro',
                    class: null,
                    formatter: Formatter::Plain,
                    template: null,
                    sortable: 'a.code',
                    hideOnMobile: false,
                ),
            ],
            filters: [],
            search: null,
            rowSignatures: [],
            defaultSort: new SortOrder(column: 'a.code', direction: 'asc'),
        );

        $page = $this->parser->parse($definition, Request::create('/', 'GET', ['sort' => 'a.code:desc']));

        self::assertNotNull($page->sort);
        self::assertSame('a.code', $page->sort->column);
        self::assertSame('desc', $page->sort->direction);
    }

    public function testUsesPerPageFromDefinition(): void
    {
        $page = $this->parser->parse($this->buildDefinition(), Request::create('/'));

        self::assertSame(25, $page->limit);
    }

    private function buildDefinition(): GridDefinition
    {
        return new GridDefinition(
            name: 'test',
            dataSource: InMemoryDataSource::class,
            filterClass: \stdClass::class,
            pagination: Pagination::PrevNext,
            perPage: 25,
            interactive: false,
            emptyMessage: '',
            renderRow: null,
            columns: [
                new ColumnDefinition(
                    propertyName: 'code',
                    label: 'Code',
                    class: null,
                    formatter: Formatter::Plain,
                    template: null,
                    sortable: 'code',
                    hideOnMobile: false,
                ),
                new ColumnDefinition(
                    propertyName: 'label',
                    label: 'Label',
                    class: null,
                    formatter: Formatter::Plain,
                    template: null,
                    sortable: false,
                    hideOnMobile: false,
                ),
            ],
            filters: [],
            search: null,
            rowSignatures: [],
            defaultSort: new SortOrder(column: 'code', direction: 'asc'),
        );
    }
}
