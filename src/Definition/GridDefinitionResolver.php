<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Filter;
use Quorae\GridBundle\Attribute\RowLink;
use Quorae\GridBundle\Attribute\RowSignature;
use Quorae\GridBundle\Attribute\Search;
use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;

/**
 * Produces an immutable {@see GridDefinition} from the attributes of a grid
 * class.
 *
 * Invoked once per grid class at compile-time (inside the compiler pass)
 * and memoised in {@see \Quorae\GridBundle\Registry\GridRegistry} — never rebuilt
 * on a request.
 *
 * Fails fast : any inconsistency (missing `#[AsGrid]`, zero columns,
 * duplicate `#[Search]`, non-static row-signature method, unknown filter
 * property on the data-source's filter DTO) is surfaced as an
 * {@see InvalidGridDefinitionException} here.
 */
final class GridDefinitionResolver
{
    /**
     * @param class-string $gridClass
     */
    public function resolve(string $gridClass): GridDefinition
    {
        $reflection = new \ReflectionClass($gridClass);
        $asGrid = $this->readAsGridAttribute($reflection);
        $filterClass = $this->resolveFilterClass($asGrid);

        $columns = $this->readColumns($reflection);
        if ($columns === [] && $asGrid->renderRow === null) {
            throw InvalidGridDefinitionException::noColumns($gridClass);
        }

        $sortValidator = new SortableValidator();
        $sortableKeys = $sortValidator->collectSortableKeys($gridClass, $columns);
        $isRenderRowGrid = $asGrid->renderRow !== null;
        $sortValidator->assertRenderRowAndSortableAreMutuallyExclusive($gridClass, $isRenderRowGrid, $sortableKeys !== []);
        $defaultSort = $sortValidator->parseDefaultSort($gridClass, $asGrid->defaultSort, $sortableKeys, $isRenderRowGrid);

        $filters = $this->readFilters($reflection, $filterClass, $gridClass);
        $search = $this->readSearch($reflection, $filterClass, $gridClass);
        $rowSignatures = $this->readRowSignatures($reflection);

        $bulkActionReader = new BulkActionReader();
        $bulkActions = $bulkActionReader->readBulkActions($reflection, $asGrid);
        $rowIdProperty = $bulkActionReader->resolveRowIdProperty($asGrid, $bulkActions, $gridClass);
        $rowLink = $this->readRowLink($reflection);

        $this->assertExpandableConstraints($asGrid, $rowLink, $gridClass);

        return new GridDefinition(
            name: $asGrid->name,
            dataSource: $asGrid->dataSource,
            filterClass: $filterClass,
            pagination: $asGrid->pagination,
            perPage: $asGrid->perPage,
            interactive: $asGrid->interactive,
            emptyMessage: $asGrid->emptyMessage,
            renderRow: $asGrid->renderRow,
            columns: $columns,
            filters: $filters,
            search: $search,
            rowSignatures: $rowSignatures,
            defaultSort: $defaultSort,
            bulkActions: $bulkActions,
            rowIdProperty: $rowIdProperty,
            rowLink: $rowLink,
            expandable: $asGrid->expandable,
            expandRoute: $asGrid->expandRoute,
            expandRouteParam: $asGrid->expandRouteParam,
            groupBy: $asGrid->groupBy,
            groupLabelProperty: $asGrid->groupLabelProperty,
        );
    }

    /**
     * Extracts the optional `#[RowLink]` attribute from the grid class.
     *
     * When present, the Twig template will emit `data-href` + `data-controller="row-link"`
     * on each `<tr>` so the Stimulus controller can handle the click.
     *
     * @param \ReflectionClass<object> $reflection
     */
    private function readRowLink(\ReflectionClass $reflection): ?RowLink
    {
        $attributes = $reflection->getAttributes(RowLink::class);
        if ($attributes === []) {
            return null;
        }

        /** @var RowLink $rowLink */
        $rowLink = $attributes[0]->newInstance();

        return $rowLink;
    }

    /**
     * Validates constraints on expandable grids :
     * - expandable: true requires an expandRoute
     * - expandable: true and #[RowLink] are mutually exclusive
     *
     * @param class-string $gridClass
     */
    private function assertExpandableConstraints(AsGrid $asGrid, ?RowLink $rowLink, string $gridClass): void
    {
        if (!$asGrid->expandable) {
            return;
        }

        if ($asGrid->expandRoute === null) {
            throw InvalidGridDefinitionException::expandableRequiresRoute($gridClass);
        }

        if ($rowLink !== null) {
            throw InvalidGridDefinitionException::expandableAndRowLinkMutuallyExclusive($gridClass);
        }
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function readAsGridAttribute(\ReflectionClass $reflection): AsGrid
    {
        $attributes = $reflection->getAttributes(AsGrid::class);
        if ($attributes === []) {
            throw InvalidGridDefinitionException::missingAsGrid($reflection->getName());
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Resolves the filter DTO class declared on `#[AsGrid]`.
     *
     * **Contract note (deviation from the frozen architecture memo)** — the
     * memo stated "Filter FQN resolved by reflection on the fetch()
     * signature". PHP forbids narrowing the type of an interface-method
     * parameter (LSP on contravariance), so {@see GridDataSource::fetch()}
     * must keep `object $filter`. The filter class is therefore declared
     * explicitly on `#[AsGrid(filterClass: BalanceFilter::class)]`. When
     * omitted, we fall back to {@see \stdClass} and skip property-name
     * validation : appropriate for a generic / dynamic filter.
     *
     * @return class-string
     */
    private function resolveFilterClass(AsGrid $asGrid): string
    {
        $dataSourceReflection = new \ReflectionClass($asGrid->dataSource);
        if (!$dataSourceReflection->implementsInterface(GridDataSource::class)) {
            throw InvalidGridDefinitionException::dataSourceMissingInterface($asGrid->dataSource);
        }

        return $asGrid->filterClass ?? \stdClass::class;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<ColumnDefinition>
     */
    private function readColumns(\ReflectionClass $reflection): array
    {
        $columns = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);
            if ($attributes === []) {
                continue;
            }
            /** @var Column $column */
            $column = $attributes[0]->newInstance();
            $columns[] = new ColumnDefinition(
                propertyName: $property->getName(),
                label: $column->label,
                class: $column->class,
                formatter: $column->formatter,
                template: $column->template,
                sortable: $column->sortable,
                hideOnMobile: $column->hideOnMobile,
                colWidth: $column->colWidth,
            );
        }

        return $columns;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @param class-string             $filterClass
     * @param class-string             $gridClass
     *
     * @return list<FilterDefinition>
     */
    private function readFilters(\ReflectionClass $reflection, string $filterClass, string $gridClass): array
    {
        $filters = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Filter::class);
            if ($attributes === []) {
                continue;
            }
            /** @var Filter $filter */
            $filter = $attributes[0]->newInstance();
            $propertyName = $property->getName();
            $this->assertFilterPropertyExists($gridClass, $propertyName, $filterClass);

            $filters[] = new FilterDefinition(
                propertyName: $propertyName,
                type: $filter->type,
                label: $filter->label ?? $propertyName,
                choices: $filter->choices,
                choicesProvider: $filter->choicesProvider,
                caption: $filter->caption,
                valueMonospace: $filter->valueMonospace,
                group: $filter->group,
            );
        }

        return $filters;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     * @param class-string             $filterClass
     * @param class-string             $gridClass
     */
    private function readSearch(\ReflectionClass $reflection, string $filterClass, string $gridClass): ?SearchDefinition
    {
        $searchProperty = null;
        $searchAttribute = null;
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Search::class);
            if ($attributes === []) {
                continue;
            }
            if ($searchProperty !== null) {
                throw InvalidGridDefinitionException::duplicateSearch($gridClass);
            }
            $searchProperty = $property;
            /** @var Search $attribute */
            $attribute = $attributes[0]->newInstance();
            $searchAttribute = $attribute;
        }

        if ($searchProperty === null || $searchAttribute === null) {
            return null;
        }

        $this->assertSearchPropertyTypeIsNullableString($gridClass, $searchProperty);
        $this->assertFilterPropertyExists($gridClass, $searchProperty->getName(), $filterClass);

        return new SearchDefinition(
            propertyName: $searchProperty->getName(),
            fields: $searchAttribute->fields,
            placeholder: $searchAttribute->placeholder,
            mode: $searchAttribute->mode,
            debounceMs: $searchAttribute->debounceMs,
        );
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<RowSignatureDefinition>
     */
    private function readRowSignatures(\ReflectionClass $reflection): array
    {
        $signatures = [];
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(RowSignature::class);
            if ($attributes === []) {
                continue;
            }
            if (!$method->isStatic()) {
                throw InvalidGridDefinitionException::rowSignatureMustBeStatic($reflection->getName(), $method->getName());
            }
            foreach ($attributes as $attribute) {
                /** @var RowSignature $rowSignature */
                $rowSignature = $attribute->newInstance();
                $signatures[] = new RowSignatureDefinition(
                    signature: $rowSignature->signature,
                    callable: [$reflection->getName(), $method->getName()],
                );
            }
        }

        return $signatures;
    }

    /**
     * @param class-string $filterClass
     * @param class-string $gridClass
     */
    private function assertFilterPropertyExists(string $gridClass, string $propertyName, string $filterClass): void
    {
        if ($filterClass === \stdClass::class) {
            // Generic `object` filter — we cannot introspect ; accept.
            return;
        }
        if (!property_exists($filterClass, $propertyName)) {
            throw InvalidGridDefinitionException::propertyNotFoundOnFilter($gridClass, $propertyName, $filterClass);
        }
    }

    /**
     * @param class-string $gridClass
     */
    private function assertSearchPropertyTypeIsNullableString(string $gridClass, \ReflectionProperty $property): void
    {
        $type = $property->getType();
        if ($type instanceof \ReflectionNamedType && $type->getName() === 'string' && $type->allowsNull()) {
            return;
        }
        $actual = $this->describeType($type);
        throw InvalidGridDefinitionException::searchPropertyMustBeNullableString($gridClass, $property->getName(), $actual);
    }

    private function describeType(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }
        if ($type instanceof \ReflectionNamedType) {
            return ($type->allowsNull() ? '?' : '').$type->getName();
        }

        return (string) $type;
    }
}
