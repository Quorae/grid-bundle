<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

/**
 * Windowing bounds handed to a {@see \Quorae\GridBundle\Contract\GridDataSource} on
 * every fetch. The data source applies `LIMIT :limit OFFSET :offset` (or
 * whatever pagination strategy it uses) and returns a
 * {@see GridResponse} with `hasNext` / `hasPrev` flags — the framework
 * never counts rows itself.
 */
final readonly class Page
{
    public function __construct(
        public int $number,
        public int $limit,
        public ?SortOrder $sort = null,
    ) {
        if ($this->number < 1) {
            throw new \InvalidArgumentException(\sprintf('Page number must be >= 1, got %d.', $this->number));
        }
        if ($this->limit < 1) {
            throw new \InvalidArgumentException(\sprintf('Page limit must be >= 1, got %d.', $this->limit));
        }
    }
}
