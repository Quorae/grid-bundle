<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Attribute\RowLink;
use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Dto\SortOrder;
use Quorae\GridBundle\Enum\Pagination;

/**
 * Compile-time description of a grid, assembled by
 * {@see GridDefinitionResolver} from the `#[AsGrid]` / `#[Column]` /
 * `#[Filter]` / `#[Search]` / `#[RowSignature]` attributes of a grid
 * class.
 *
 * Held in the {@see \Quorae\GridBundle\Registry\GridRegistry} map and handed to the
 * Twig component layer through a {@see \Quorae\GridBundle\Dto\GridView} — never
 * rebuilt at request time.
 */
final readonly class GridDefinition
{
    /**
     * @param class-string<GridDataSource> $dataSource
     * @param class-string                 $filterClass
     * @param list<ColumnDefinition>       $columns
     * @param list<FilterDefinition>       $filters
     * @param list<RowSignatureDefinition> $rowSignatures
     * @param list<BulkActionDefinition>   $bulkActions
     * @param ?string                      $rowIdProperty      name of the property on the row DTO exposing the scalar id consumed by bulk-action selection
     * @param ?RowLink                     $rowLink            when set, each table row renders a clickable link to a detail page via the Stimulus `row-link` controller
     * @param bool                         $expandable         when true, rows can be toggled open to reveal an inline detail panel loaded via Turbo Frame
     * @param ?string                      $expandRoute        Symfony route name that serves the expanded row content (required when expandable is true)
     * @param string                       $expandRouteParam   route parameter name injected with the row's id value
     * @param ?string                      $groupBy            row DTO property used as group key — when set and the filter on that property is null, section headers are rendered between groups
     * @param ?string                      $groupLabelProperty row DTO property holding the human label for each group
     */
    public function __construct(
        public string $name,
        public string $dataSource,
        public string $filterClass,
        public Pagination $pagination,
        public int $perPage,
        public bool $interactive,
        public string $emptyMessage,
        public ?string $renderRow,
        public array $columns,
        public array $filters,
        public ?SearchDefinition $search,
        public array $rowSignatures,
        public ?SortOrder $defaultSort = null,
        public array $bulkActions = [],
        public ?string $rowIdProperty = null,
        public ?RowLink $rowLink = null,
        public bool $expandable = false,
        public ?string $expandRoute = null,
        public string $expandRouteParam = 'id',
        public ?string $groupBy = null,
        public ?string $groupLabelProperty = null,
    ) {
    }

    /**
     * Returns a copy with the given filters — every other property is
     * forwarded explicitly from `$this`.
     *
     * @param list<FilterDefinition> $filters
     */
    public function withFilters(array $filters): self
    {
        return new self(
            name: $this->name,
            dataSource: $this->dataSource,
            filterClass: $this->filterClass,
            pagination: $this->pagination,
            perPage: $this->perPage,
            interactive: $this->interactive,
            emptyMessage: $this->emptyMessage,
            renderRow: $this->renderRow,
            columns: $this->columns,
            filters: $filters,
            search: $this->search,
            rowSignatures: $this->rowSignatures,
            defaultSort: $this->defaultSort,
            bulkActions: $this->bulkActions,
            rowIdProperty: $this->rowIdProperty,
            rowLink: $this->rowLink,
            expandable: $this->expandable,
            expandRoute: $this->expandRoute,
            expandRouteParam: $this->expandRouteParam,
            groupBy: $this->groupBy,
            groupLabelProperty: $this->groupLabelProperty,
        );
    }
}
