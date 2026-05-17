<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

use Quorae\GridBundle\Enum\SearchMode;

/**
 * Declares the first-class search input rendered above the grid. A grid
 * carries at most one `#[Search]` — the resolver rejects duplicates at
 * compile-time.
 *
 * The annotated property must be typed `?string` : the search string flows
 * directly into the hydrated filter DTO, which the data source then consumes.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Search
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        public array $fields,
        public string $placeholder = 'Rechercher…',
        public SearchMode $mode = SearchMode::Contains,
        public int $debounceMs = 300,
    ) {
    }
}
