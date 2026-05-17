<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Dto\GridResponse;
use Quorae\GridBundle\Dto\Page;

/**
 * In-memory substitute for a real ACD / App DB data source — lets unit tests
 * drive the framework without a database.
 *
 * The concrete `fetch()` signature keeps the interface's `object $filter`
 * type (LSP — PHP forbids narrowing parameter types of interface methods)
 * and narrows to {@see DummyFilter} at runtime. The resolver learns the
 * concrete filter class via `#[AsGrid(filterClass: DummyFilter::class)]`.
 */
final class InMemoryDataSource implements GridDataSource
{
    /** @var list<object> */
    public array $capturedRows = [];

    public ?DummyFilter $capturedFilter = null;

    public ?Page $capturedPage = null;

    /**
     * @param list<object> $rows
     */
    public function __construct(private array $rows = [])
    {
    }

    public function fetch(object $filter, Page $page): GridResponse
    {
        \assert($filter instanceof DummyFilter);
        $this->capturedFilter = $filter;
        $this->capturedPage = $page;
        $this->capturedRows = $this->rows;

        return new GridResponse(
            rows: $this->rows,
            hasNext: false,
            hasPrev: $page->number > 1,
            page: $page->number,
        );
    }
}
