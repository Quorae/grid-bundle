<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Enum\Pagination;

/**
 * Marks a class as a grid declaration — the framework auto-discovers every
 * `#[AsGrid]` class at compile-time and derives a `GridDefinition` from the
 * property-level `#[Column]`, `#[Filter]`, `#[Search]` attributes it carries.
 *
 * The annotated class never contains logic — it is a declarative schema,
 * read by reflection.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsGrid
{
    /**
     * @param class-string<GridDataSource> $dataSource
     * @param class-string|null            $filterClass the concrete filter DTO the data source
     *                                                  expects ; resolved by reflecting the grid
     *                                                  class itself when omitted (the grid's
     *                                                  public non-static properties are copied
     *                                                  into the filter by name)
     * @param class-string|null            $rowClass    the row DTO emitted by the data source —
     *                                                  **required** when the grid declares any
     *                                                  `#[BulkAction]`, because the framework
     *                                                  needs to locate the scalar id property
     *                                                  on the row (via `#[RowId]` or by the
     *                                                  `id` convention)
     */
    public function __construct(
        public string $name,
        public string $dataSource,
        public ?string $filterClass = null,
        public Pagination $pagination = Pagination::Offset,
        public int $perPage = 50,
        public bool $interactive = false,
        public string $emptyMessage = 'Aucun élément.',
        public ?string $renderRow = null,
        public ?string $defaultSort = null,
        public ?string $rowClass = null,
        public bool $expandable = false,
        public ?string $expandRoute = null,
        public string $expandRouteParam = 'id',
        public ?string $groupBy = null,
        public ?string $groupLabelProperty = null,
    ) {
    }
}
