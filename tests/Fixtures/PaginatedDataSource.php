<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Dto\GridResponse;
use Quorae\GridBundle\Dto\Page;

/**
 * Offset-paginated in-memory data source that knows its `totalPages` — used
 * to exercise the handler's out-of-range page clamp (spec §9). Records every
 * fetched page number so a test can assert the handler re-fetched at the
 * clamped last page rather than returning an empty grid.
 */
final class PaginatedDataSource implements GridDataSource
{
    /** @var list<int> page numbers received, in call order */
    public array $fetchedPages = [];

    /**
     * @param int $totalRows  total number of rows the backend holds
     * @param int $perPage    page size — drives totalPages and the row slice
     */
    public function __construct(
        private int $totalRows,
        private int $perPage,
    ) {
    }

    public function fetch(object $filter, Page $page): GridResponse
    {
        \assert($filter instanceof CamelCaseFilter);
        $this->fetchedPages[] = $page->number;

        $totalPages = (int) max(1, (int) ceil($this->totalRows / $this->perPage));
        $offset = ($page->number - 1) * $this->perPage;
        $remaining = max(0, $this->totalRows - $offset);
        $rowsOnPage = min($this->perPage, $remaining);

        $rows = [];
        for ($i = 0; $i < $rowsOnPage; ++$i) {
            $row = new \stdClass();
            $row->code = (string) ($offset + $i + 1);
            $rows[] = $row;
        }

        return new GridResponse(
            rows: $rows,
            hasNext: $page->number < $totalPages,
            hasPrev: $page->number > 1,
            page: $page->number,
            totalCount: $this->totalRows,
            totalPages: $totalPages,
        );
    }
}
