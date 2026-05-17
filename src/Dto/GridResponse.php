<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

/**
 * The output contract of {@see \Quorae\GridBundle\Contract\GridDataSource::fetch()}.
 *
 * Row objects can be anything — value objects, entities, DTOs : the
 * framework never inspects them at the field level. Column extraction is
 * delegated to the Twig component layer, which reads each column's property
 * name from the grid definition.
 */
final readonly class GridResponse
{
    /**
     * @param array<int, object> $rows
     * @param array<string, int> $groupCounts total count per group key (populated when the grid declares a groupBy)
     */
    public function __construct(
        public array $rows,
        public bool $hasNext,
        public bool $hasPrev,
        public int $page,
        public array $groupCounts = [],
        public ?int $totalCount = null,
        public ?int $totalPages = null,
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException(\sprintf('GridResponse page must be >= 1, got %d.', $this->page));
        }
    }
}
