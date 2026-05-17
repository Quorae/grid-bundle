<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

/**
 * A single `ORDER BY` clause applied to a grid.
 *
 * `direction` is strictly lower-case `'asc'` or `'desc'` — upstream parsing
 * (query string → `SortOrder`) is expected to normalize before instantiating.
 */
final readonly class SortOrder
{
    public function __construct(
        public string $column,
        public string $direction,
    ) {
        if ($this->column === '') {
            throw new \InvalidArgumentException('Sort column must not be empty.');
        }
        if ($this->direction !== 'asc' && $this->direction !== 'desc') {
            throw new \InvalidArgumentException(\sprintf("Sort direction must be 'asc' or 'desc', got '%s'.", $this->direction));
        }
    }
}
