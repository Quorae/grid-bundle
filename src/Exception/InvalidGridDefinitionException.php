<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Exception;

/**
 * Raised at compile-time (compiler pass) or at first resolve of a grid
 * class that violates the framework's contract — missing `#[AsGrid]`,
 * duplicate name, no columns declared, `#[Search]` on a wrong-typed
 * property, a `#[RowSignature]` on a non-static method, etc.
 *
 * Factory methods centralise the exact wording so callers don't drift.
 */
final class InvalidGridDefinitionException extends \LogicException implements GridExceptionInterface
{
    public static function missingAsGrid(string $class): self
    {
        return new self(\sprintf(
            'Class "%s" is not annotated with #[Quorae\\GridBundle\\Attribute\\AsGrid].',
            $class,
        ));
    }

    public static function noColumns(string $class): self
    {
        return new self(\sprintf(
            'Grid class "%s" declares zero columns — at least one #[Column] is required.',
            $class,
        ));
    }

    public static function duplicateName(string $name, string $firstClass, string $secondClass): self
    {
        return new self(\sprintf(
            'Grid name "%s" is declared twice : by "%s" and by "%s". Names must be unique.',
            $name,
            $firstClass,
            $secondClass,
        ));
    }

    public static function duplicateSearch(string $class): self
    {
        return new self(\sprintf(
            'Grid class "%s" declares more than one #[Search] attribute — at most one is allowed.',
            $class,
        ));
    }

    public static function searchPropertyMustBeNullableString(string $class, string $property, string $actualType): self
    {
        return new self(\sprintf(
            'Property "%s::$%s" carries #[Search] but is typed "%s" — expected "?string".',
            $class,
            $property,
            $actualType,
        ));
    }

    public static function rowSignatureMustBeStatic(string $class, string $method): self
    {
        return new self(\sprintf(
            'Method "%s::%s()" carries #[RowSignature] but is not static.',
            $class,
            $method,
        ));
    }

    public static function propertyNotFoundOnFilter(string $gridClass, string $property, string $filterClass): self
    {
        return new self(\sprintf(
            'Grid "%s" exposes filter/search property "$%s" that is not declared on its filter DTO "%s".',
            $gridClass,
            $property,
            $filterClass,
        ));
    }

    public static function dataSourceMissingInterface(string $dataSourceClass): self
    {
        return new self(\sprintf(
            'Data source "%s" does not implement GridDataSource.',
            $dataSourceClass,
        ));
    }

    public static function defaultSortRequired(string $gridClass): self
    {
        return new self(\sprintf(
            'Grid "%s" declares sortable columns but no #[AsGrid(defaultSort: ...)] — declare a default (e.g. defaultSort: "key:asc").',
            $gridClass,
        ));
    }

    public static function defaultSortReferencesUnknownColumn(string $gridClass, string $column): self
    {
        return new self(\sprintf(
            'Grid "%s" declares defaultSort on column "%s" which is not a declared sortable column.',
            $gridClass,
            $column,
        ));
    }

    public static function defaultSortMalformed(string $gridClass, string $raw): self
    {
        return new self(\sprintf(
            'Grid "%s" declares defaultSort "%s" — expected the form "<key>:asc" or "<key>:desc".',
            $gridClass,
            $raw,
        ));
    }

    public static function sortableOnRenderRowGrid(string $gridClass): self
    {
        return new self(\sprintf(
            'Grid "%s" combines renderRow with sortable columns — sorting is not supported on card layouts.',
            $gridClass,
        ));
    }

    public static function duplicateSortableKey(string $gridClass, string $key): self
    {
        return new self(\sprintf(
            'Grid "%s" declares the sortable key "%s" on more than one column — keys must be unique.',
            $gridClass,
            $key,
        ));
    }

    public static function bulkActionRequiresInteractive(string $gridClass, string $actionName): self
    {
        return new self(\sprintf(
            'Grid "%s" declares bulk action "%s" but is not #[AsGrid(interactive: true)] — bulk actions require a Live Component.',
            $gridClass,
            $actionName,
        ));
    }

    public static function bulkActionRequiresRowId(string $gridClass, string $rowDtoClass): self
    {
        return new self(\sprintf(
            'Grid "%s" declares bulk actions but its row DTO "%s" exposes neither a #[RowId] property nor a public "id" property of type int|string.',
            $gridClass,
            $rowDtoClass,
        ));
    }

    public static function duplicateBulkActionName(string $gridClass, string $actionName): self
    {
        return new self(\sprintf(
            'Grid "%s" declares the bulk action name "%s" more than once — names must be unique per grid.',
            $gridClass,
            $actionName,
        ));
    }

    public static function bulkActionHandlerMissingInterface(string $gridClass, string $handlerFqcn): self
    {
        return new self(\sprintf(
            'Grid "%s" references bulk action handler "%s" which does not implement Quorae\\GridBundle\\Contract\\BulkActionHandler.',
            $gridClass,
            $handlerFqcn,
        ));
    }

    public static function duplicateRowIdAttribute(string $gridClass, string $rowDtoClass): self
    {
        return new self(\sprintf(
            'Grid "%s" row DTO "%s" declares more than one #[RowId] attribute — at most one is allowed.',
            $gridClass,
            $rowDtoClass,
        ));
    }

    public static function expandableRequiresRoute(string $gridClass): self
    {
        return new self(\sprintf(
            'Grid "%s" declares expandable: true but no expandRoute — an expandRoute is required when rows are expandable.',
            $gridClass,
        ));
    }

    public static function expandableAndRowLinkMutuallyExclusive(string $gridClass): self
    {
        return new self(\sprintf(
            'Grid "%s" declares both expandable: true and #[RowLink] — these are mutually exclusive.',
            $gridClass,
        ));
    }

    public static function ownershipValidatorMissingInterface(string $gridClass, string $validatorFqcn): self
    {
        return new self(\sprintf(
            'Grid "%s" references ownership validator "%s" which does not implement Quorae\\GridBundle\\Contract\\BulkOwnershipValidator.',
            $gridClass,
            $validatorFqcn,
        ));
    }
}
