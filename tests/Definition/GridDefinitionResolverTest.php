<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Definition;

use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Definition\GridDefinitionResolver;
use Quorae\GridBundle\Enum\FilterType;
use Quorae\GridBundle\Enum\Formatter;
use Quorae\GridBundle\Enum\LedgerSignature;
use Quorae\GridBundle\Enum\Pagination;
use Quorae\GridBundle\Enum\SearchMode;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;
use Quorae\GridBundle\Tests\Fixtures\BadSearchTypeGrid;
use Quorae\GridBundle\Tests\Fixtures\BulkActionStubHandler;
use Quorae\GridBundle\Tests\Fixtures\BulkGridDuplicateActionName;
use Quorae\GridBundle\Tests\Fixtures\BulkGridDuplicateRowId;
use Quorae\GridBundle\Tests\Fixtures\BulkGridHandlerMissingInterface;
use Quorae\GridBundle\Tests\Fixtures\BulkGridHappyPath;
use Quorae\GridBundle\Tests\Fixtures\BulkGridInteractiveFalse;
use Quorae\GridBundle\Tests\Fixtures\BulkGridMissingRowId;
use Quorae\GridBundle\Tests\Fixtures\BulkGridWithRowIdAttribute;
use Quorae\GridBundle\Tests\Fixtures\ClasseChoicesProvider;
use Quorae\GridBundle\Tests\Fixtures\CompleteGrid;
use Quorae\GridBundle\Tests\Fixtures\DummyFilter;
use Quorae\GridBundle\Tests\Fixtures\DuplicateSearchGrid;
use Quorae\GridBundle\Tests\Fixtures\FilterBarCaptionGrid;
use Quorae\GridBundle\Tests\Fixtures\GridWithDefaultSortBadDirection;
use Quorae\GridBundle\Tests\Fixtures\GridWithDefaultSortMalformed;
use Quorae\GridBundle\Tests\Fixtures\GridWithDefaultSortUnknownColumn;
use Quorae\GridBundle\Tests\Fixtures\GridWithDuplicateSortableKey;
use Quorae\GridBundle\Tests\Fixtures\GridWithSortableNoDefault;
use Quorae\GridBundle\Tests\Fixtures\GridWithSortableOnRenderRow;
use Quorae\GridBundle\Tests\Fixtures\GridWithValidDefaultSort;
use Quorae\GridBundle\Tests\Fixtures\MinimalGrid;
use Quorae\GridBundle\Tests\Fixtures\MissingAsGridFixture;
use Quorae\GridBundle\Tests\Fixtures\NoColumnsGrid;
use Quorae\GridBundle\Tests\Fixtures\NonStaticRowSignatureGrid;
use Quorae\GridBundle\Tests\Fixtures\ProviderBackedFilterGrid;
use Quorae\GridBundle\Tests\Fixtures\UnknownFilterPropertyGrid;
use PHPUnit\Framework\TestCase;

final class GridDefinitionResolverTest extends TestCase
{
    private GridDefinitionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new GridDefinitionResolver();
    }

    public function testResolvesCompleteGridWithEveryAttribute(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertInstanceOf(GridDefinition::class, $definition);
        self::assertSame('fixture_complete', $definition->name);
        self::assertSame(Pagination::PrevNext, $definition->pagination);
        self::assertSame(25, $definition->perPage);
        self::assertFalse($definition->interactive);
        self::assertSame('Rien ici.', $definition->emptyMessage);
        self::assertNull($definition->renderRow);
        self::assertSame(DummyFilter::class, $definition->filterClass);
    }

    public function testResolvesAllColumnsInDeclarationOrder(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertCount(3, $definition->columns);
        self::assertSame('code', $definition->columns[0]->propertyName);
        self::assertSame('Code', $definition->columns[0]->label);
        self::assertSame('code', $definition->columns[0]->sortable);
        self::assertSame(Formatter::Plain, $definition->columns[0]->formatter);

        self::assertSame('label', $definition->columns[1]->propertyName);
        self::assertSame('lbl', $definition->columns[1]->class);

        self::assertSame('solde', $definition->columns[2]->propertyName);
        self::assertSame(Formatter::Badge, $definition->columns[2]->formatter);
        self::assertTrue($definition->columns[2]->hideOnMobile);
    }

    public function testResolvesFiltersAndSearch(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertCount(2, $definition->filters);
        self::assertSame('classe', $definition->filters[0]->propertyName);
        self::assertSame(FilterType::Pills, $definition->filters[0]->type);
        self::assertSame('Classe', $definition->filters[0]->label);
        self::assertSame([1, 2, 3], $definition->filters[0]->choices);

        self::assertSame('revisedOnly', $definition->filters[1]->propertyName);
        self::assertSame(FilterType::Toggle, $definition->filters[1]->type);
        // Label defaults to the property name when not provided.
        self::assertSame('revisedOnly', $definition->filters[1]->label);

        self::assertNotNull($definition->search);
        self::assertSame('q', $definition->search->propertyName);
        self::assertSame(['code', 'label'], $definition->search->fields);
        self::assertSame(SearchMode::Contains, $definition->search->mode);
        self::assertSame(400, $definition->search->debounceMs);
    }

    public function testResolvesRowSignatureMethodAsStaticCallable(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertCount(1, $definition->rowSignatures);
        self::assertSame(LedgerSignature::AnomalyBar, $definition->rowSignatures[0]->signature);
        self::assertSame([CompleteGrid::class, 'hasAnomaly'], $definition->rowSignatures[0]->callable);
    }

    public function testMinimalGridIsAcceptedWithSingleColumn(): void
    {
        $definition = $this->resolver->resolve(MinimalGrid::class);

        self::assertSame('fixture_minimal', $definition->name);
        self::assertCount(1, $definition->columns);
        self::assertSame([], $definition->filters);
        self::assertNull($definition->search);
        self::assertSame([], $definition->rowSignatures);
    }

    /**
     * Delta A (port-map §2.A.1) : the `#[AsGrid]` pagination default flips
     * from `PrevNext` to `Offset`. A grid that omits `pagination` must
     * resolve to `Pagination::Offset`. `CompleteGrid` still works because it
     * declares `pagination: PrevNext` explicitly (honoured verbatim).
     */
    public function testDefaultPaginationIsOffsetWhenOmitted(): void
    {
        $definition = $this->resolver->resolve(MinimalGrid::class);

        self::assertSame(Pagination::Offset, $definition->pagination);
    }

    public function testExplicitPrevNextPaginationIsHonoured(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertSame(Pagination::PrevNext, $definition->pagination);
    }

    public function testRejectsClassWithoutAsGridAttribute(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('is not annotated with #[Quorae\\GridBundle\\Attribute\\AsGrid]');

        $this->resolver->resolve(MissingAsGridFixture::class);
    }

    public function testRejectsGridWithoutColumns(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('declares zero columns');

        $this->resolver->resolve(NoColumnsGrid::class);
    }

    public function testRejectsGridWithMoreThanOneSearchAttribute(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('declares more than one #[Search]');

        $this->resolver->resolve(DuplicateSearchGrid::class);
    }

    public function testRejectsSearchOnNonNullableStringProperty(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('carries #[Search] but is typed "int"');

        $this->resolver->resolve(BadSearchTypeGrid::class);
    }

    public function testRejectsNonStaticRowSignatureMethod(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('carries #[RowSignature] but is not static');

        $this->resolver->resolve(NonStaticRowSignatureGrid::class);
    }

    public function testKeepsChoicesProviderClassStringForLateResolution(): void
    {
        $definition = $this->resolver->resolve(ProviderBackedFilterGrid::class);

        self::assertCount(1, $definition->filters);
        self::assertSame(ClasseChoicesProvider::class, $definition->filters[0]->choicesProvider);
        self::assertSame([], $definition->filters[0]->choices);
    }

    public function testRejectsFilterPropertyMissingOnFilterDto(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('unknownField');

        $this->resolver->resolve(UnknownFilterPropertyGrid::class);
    }

    public function testResolvesDefaultSortWhenDeclared(): void
    {
        $definition = $this->resolver->resolve(GridWithValidDefaultSort::class);

        self::assertNotNull($definition->defaultSort);
        self::assertSame('code', $definition->defaultSort->column);
        self::assertSame('asc', $definition->defaultSort->direction);
    }

    public function testRejectsSortableWithoutDefaultSort(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('declares sortable columns but no #[AsGrid(defaultSort');

        $this->resolver->resolve(GridWithSortableNoDefault::class);
    }

    public function testRejectsDefaultSortReferencingUnknownColumn(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('defaultSort on column "nope"');

        $this->resolver->resolve(GridWithDefaultSortUnknownColumn::class);
    }

    public function testRejectsDefaultSortWithInvalidDirection(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('expected the form "<key>:asc" or "<key>:desc"');

        $this->resolver->resolve(GridWithDefaultSortBadDirection::class);
    }

    public function testRejectsDefaultSortMalformedString(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('expected the form "<key>:asc" or "<key>:desc"');

        $this->resolver->resolve(GridWithDefaultSortMalformed::class);
    }

    public function testRejectsSortableOnRenderRowGrid(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('combines renderRow with sortable columns');

        $this->resolver->resolve(GridWithSortableOnRenderRow::class);
    }

    public function testRejectsDuplicateSortableKey(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('declares the sortable key "code" on more than one column');

        $this->resolver->resolve(GridWithDuplicateSortableKey::class);
    }

    public function testResolvesBulkActionsInDeclarationOrderWithConventionRowId(): void
    {
        $definition = $this->resolver->resolve(BulkGridHappyPath::class);

        self::assertCount(2, $definition->bulkActions);
        self::assertSame('delete', $definition->bulkActions[0]->name);
        self::assertSame('Supprimer', $definition->bulkActions[0]->label);
        self::assertSame(BulkActionStubHandler::class, $definition->bulkActions[0]->handlerService);
        self::assertTrue($definition->bulkActions[0]->destructive);
        self::assertSame('heroicons:trash-16-solid', $definition->bulkActions[0]->icon);
        self::assertSame('Supprimer {count} éléments ?', $definition->bulkActions[0]->confirmMessage);
        self::assertSame('ROLE_USER', $definition->bulkActions[0]->requiredRole);

        self::assertSame('archive', $definition->bulkActions[1]->name);
        self::assertFalse($definition->bulkActions[1]->destructive);
        self::assertNull($definition->bulkActions[1]->icon);

        // Convention: public int $id property → rowIdProperty = 'id'
        self::assertSame('id', $definition->rowIdProperty);
    }

    public function testResolvesRowIdAttributeWhenDeclaredOnRowDto(): void
    {
        $definition = $this->resolver->resolve(BulkGridWithRowIdAttribute::class);

        self::assertSame('publicCode', $definition->rowIdProperty);
    }

    public function testRejectsBulkActionGridWhenInteractiveIsFalse(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('is not #[AsGrid(interactive: true)]');

        $this->resolver->resolve(BulkGridInteractiveFalse::class);
    }

    public function testRejectsBulkActionGridWhenRowDtoHasNoId(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('neither a #[RowId] property nor a public "id" property');

        $this->resolver->resolve(BulkGridMissingRowId::class);
    }

    public function testRejectsDuplicateBulkActionName(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('"delete" more than once');

        $this->resolver->resolve(BulkGridDuplicateActionName::class);
    }

    public function testRejectsBulkActionHandlerMissingInterface(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('does not implement Quorae\\GridBundle\\Contract\\BulkActionHandler');

        $this->resolver->resolve(BulkGridHandlerMissingInterface::class);
    }

    public function testRejectsDuplicateRowIdAttribute(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('more than one #[RowId]');

        $this->resolver->resolve(BulkGridDuplicateRowId::class);
    }

    public function testReturnsEmptyBulkActionsAndNullRowIdPropertyOnStandardGrid(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertSame([], $definition->bulkActions);
        self::assertNull($definition->rowIdProperty);
    }

    public function testFilterCaptionDefaultsToNull(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertNull($definition->filters[0]->caption);
    }

    public function testFilterCaptionExplicitValue(): void
    {
        $definition = $this->resolver->resolve(FilterBarCaptionGrid::class);

        self::assertSame('Filtrer par classe comptable', $definition->filters[0]->caption);
    }

    public function testFilterValueMonospaceDefaultsToFalse(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertFalse($definition->filters[0]->valueMonospace);
    }

    public function testFilterValueMonospaceExplicitTrue(): void
    {
        $definition = $this->resolver->resolve(FilterBarCaptionGrid::class);

        self::assertTrue($definition->filters[0]->valueMonospace);
    }

    public function testFilterGroupDefaultsToNull(): void
    {
        $definition = $this->resolver->resolve(CompleteGrid::class);

        self::assertNull($definition->filters[0]->group);
    }

    public function testFilterGroupExplicitValue(): void
    {
        $definition = $this->resolver->resolve(FilterBarCaptionGrid::class);

        self::assertSame('comptabilité', $definition->filters[0]->group);
    }

    public function testFilterDefaultsPreservedOnSecondFilter(): void
    {
        $definition = $this->resolver->resolve(FilterBarCaptionGrid::class);

        $toggle = $definition->filters[1];
        self::assertNull($toggle->caption);
        self::assertFalse($toggle->valueMonospace);
        self::assertNull($toggle->group);
    }
}
