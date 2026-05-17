<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Enum\SearchMode;

/**
 * Compile-time snapshot of the `#[Search]` attribute of a grid. A grid
 * carries at most one — the resolver enforces uniqueness.
 */
final readonly class SearchDefinition
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        public string $propertyName,
        public array $fields,
        public string $placeholder,
        public SearchMode $mode,
        public int $debounceMs,
    ) {
    }
}
