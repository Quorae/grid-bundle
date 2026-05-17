<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

use Quorae\GridBundle\Definition\GridDefinition;

/**
 * Immutable aggregate passed to the grid Twig component — everything the
 * template needs to render a grid in a single struct.
 *
 * `filter` is the hydrated filter DTO consumed by the data source ; the Twig
 * component inspects it to re-populate filter widgets.
 *
 * `frameId` follows the convention `"grid-{name}"` and is used both by the
 * Turbo Frame shell and by the Live Component root element id.
 */
final readonly class GridView
{
    public function __construct(
        public GridDefinition $definition,
        public GridResponse $response,
        public object $filter,
        public ?SortOrder $sort,
        public string $frameId,
        public string $pageParam = 'p',
    ) {
    }
}
