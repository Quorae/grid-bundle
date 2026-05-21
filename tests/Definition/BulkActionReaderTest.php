<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Definition;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Definition\BulkActionReader;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;
use Quorae\GridBundle\Tests\Fixtures\BulkActionStubHandler;
use Quorae\GridBundle\Tests\Fixtures\BulkGridDuplicateActionName;
use Quorae\GridBundle\Tests\Fixtures\BulkGridDuplicateRowId;
use Quorae\GridBundle\Tests\Fixtures\BulkGridHandlerMissingInterface;
use Quorae\GridBundle\Tests\Fixtures\BulkGridHappyPath;
use Quorae\GridBundle\Tests\Fixtures\BulkGridInteractiveFalse;
use Quorae\GridBundle\Tests\Fixtures\BulkGridMissingRowId;
use Quorae\GridBundle\Tests\Fixtures\BulkGridMixedHandlerAndRoute;
use Quorae\GridBundle\Tests\Fixtures\BulkGridValidatorMissingInterface;
use Quorae\GridBundle\Tests\Fixtures\BulkGridWithHandlerAndRoute;
use Quorae\GridBundle\Tests\Fixtures\BulkGridWithRoute;
use Quorae\GridBundle\Tests\Fixtures\BulkGridWithRowIdAttribute;
use Quorae\GridBundle\Tests\Fixtures\BulkGridWithoutHandlerOrRoute;
use Quorae\GridBundle\Tests\Fixtures\StubOwnershipValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the extracted {@see BulkActionReader} — covers all paths
 * related to `#[BulkAction]` attribute reading and `#[RowId]` resolution.
 */
final class BulkActionReaderTest extends TestCase
{
    private BulkActionReader $reader;

    protected function setUp(): void
    {
        $this->reader = new BulkActionReader();
    }

    public function testReadsBulkActionsInDeclarationOrder(): void
    {
        $ref = new \ReflectionClass(BulkGridHappyPath::class);
        $asGrid = $this->readAsGrid($ref);

        $actions = $this->reader->readBulkActions($ref, $asGrid);

        self::assertCount(2, $actions);
        self::assertSame('delete', $actions[0]->name);
        self::assertSame('Supprimer', $actions[0]->label);
        self::assertSame(BulkActionStubHandler::class, $actions[0]->handlerService);
        self::assertSame(StubOwnershipValidator::class, $actions[0]->ownershipValidator);
        self::assertTrue($actions[0]->destructive);
        self::assertSame('archive', $actions[1]->name);
    }

    public function testRejectsNonInteractiveGrid(): void
    {
        $ref = new \ReflectionClass(BulkGridInteractiveFalse::class);
        $asGrid = $this->readAsGrid($ref);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('is not #[AsGrid(interactive: true)]');

        $this->reader->readBulkActions($ref, $asGrid);
    }

    public function testRejectsDuplicateActionName(): void
    {
        $ref = new \ReflectionClass(BulkGridDuplicateActionName::class);
        $asGrid = $this->readAsGrid($ref);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('"delete" more than once');

        $this->reader->readBulkActions($ref, $asGrid);
    }

    public function testRejectsHandlerMissingInterface(): void
    {
        $ref = new \ReflectionClass(BulkGridHandlerMissingInterface::class);
        $asGrid = $this->readAsGrid($ref);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('does not implement Quorae\\GridBundle\\Contract\\BulkActionHandler');

        $this->reader->readBulkActions($ref, $asGrid);
    }

    public function testRejectsValidatorMissingInterface(): void
    {
        $ref = new \ReflectionClass(BulkGridValidatorMissingInterface::class);
        $asGrid = $this->readAsGrid($ref);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('does not implement Quorae\\GridBundle\\Contract\\BulkOwnershipValidator');

        $this->reader->readBulkActions($ref, $asGrid);
    }

    public function testReturnsEmptyListWhenNoBulkActions(): void
    {
        $ref = new \ReflectionClass(\Quorae\GridBundle\Tests\Fixtures\CompleteGrid::class);
        $asGrid = $this->readAsGrid($ref);

        $actions = $this->reader->readBulkActions($ref, $asGrid);

        self::assertSame([], $actions);
    }

    public function testResolvesConventionalIdProperty(): void
    {
        $ref = new \ReflectionClass(BulkGridHappyPath::class);
        $asGrid = $this->readAsGrid($ref);
        $bulkActions = $this->reader->readBulkActions($ref, $asGrid);

        $rowIdProperty = $this->reader->resolveRowIdProperty($asGrid, $bulkActions, BulkGridHappyPath::class);

        self::assertSame('id', $rowIdProperty);
    }

    public function testResolvesAttributedRowIdProperty(): void
    {
        $ref = new \ReflectionClass(BulkGridWithRowIdAttribute::class);
        $asGrid = $this->readAsGrid($ref);
        $bulkActions = $this->reader->readBulkActions($ref, $asGrid);

        $rowIdProperty = $this->reader->resolveRowIdProperty($asGrid, $bulkActions, BulkGridWithRowIdAttribute::class);

        self::assertSame('publicCode', $rowIdProperty);
    }

    public function testReturnsNullRowIdWhenNoBulkActions(): void
    {
        $ref = new \ReflectionClass(\Quorae\GridBundle\Tests\Fixtures\CompleteGrid::class);
        $asGrid = $this->readAsGrid($ref);

        $rowIdProperty = $this->reader->resolveRowIdProperty($asGrid, [], \Quorae\GridBundle\Tests\Fixtures\CompleteGrid::class);

        self::assertNull($rowIdProperty);
    }

    public function testRejectsMissingRowId(): void
    {
        $ref = new \ReflectionClass(BulkGridMissingRowId::class);
        $asGrid = $this->readAsGrid($ref);
        $bulkActions = $this->reader->readBulkActions($ref, $asGrid);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('neither a #[RowId] property nor a public "id" property');

        $this->reader->resolveRowIdProperty($asGrid, $bulkActions, BulkGridMissingRowId::class);
    }

    public function testRejectsDuplicateRowIdAttribute(): void
    {
        $ref = new \ReflectionClass(BulkGridDuplicateRowId::class);
        $asGrid = $this->readAsGrid($ref);
        $bulkActions = $this->reader->readBulkActions($ref, $asGrid);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('more than one #[RowId]');

        $this->reader->resolveRowIdProperty($asGrid, $bulkActions, BulkGridDuplicateRowId::class);
    }

    public function testReadsRouteBulkAction(): void
    {
        $ref = new \ReflectionClass(BulkGridWithRoute::class);
        $asGrid = $this->readAsGrid($ref);

        $actions = $this->reader->readBulkActions($ref, $asGrid);

        self::assertCount(1, $actions);
        self::assertSame('batch_remediation', $actions[0]->name);
        self::assertSame('Remédier la sélection', $actions[0]->label);
        self::assertSame('app_batch_remediation_selector', $actions[0]->route);
        self::assertNull($actions[0]->handlerService);
        self::assertNull($actions[0]->ownershipValidator);
        self::assertSame('heroicons:wrench-16-solid', $actions[0]->icon);
    }

    public function testRejectsHandlerAndRouteTogether(): void
    {
        $ref = new \ReflectionClass(BulkGridWithHandlerAndRoute::class);
        $asGrid = $this->readAsGrid($ref);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('mutually exclusive');

        $this->reader->readBulkActions($ref, $asGrid);
    }

    public function testRejectsNeitherHandlerNorRoute(): void
    {
        $ref = new \ReflectionClass(BulkGridWithoutHandlerOrRoute::class);
        $asGrid = $this->readAsGrid($ref);

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('must declare either');

        $this->reader->readBulkActions($ref, $asGrid);
    }

    public function testMixedHandlerAndRouteActionsCoexist(): void
    {
        $ref = new \ReflectionClass(BulkGridMixedHandlerAndRoute::class);
        $asGrid = $this->readAsGrid($ref);

        $actions = $this->reader->readBulkActions($ref, $asGrid);

        self::assertCount(2, $actions);

        self::assertSame('delete', $actions[0]->name);
        self::assertNotNull($actions[0]->handlerService);
        self::assertNull($actions[0]->route);

        self::assertSame('batch_remediation', $actions[1]->name);
        self::assertNull($actions[1]->handlerService);
        self::assertSame('app_batch_remediation', $actions[1]->route);
    }

    public function testRouteActionResolvesRowIdProperty(): void
    {
        $ref = new \ReflectionClass(BulkGridWithRoute::class);
        $asGrid = $this->readAsGrid($ref);
        $bulkActions = $this->reader->readBulkActions($ref, $asGrid);

        $rowIdProperty = $this->reader->resolveRowIdProperty($asGrid, $bulkActions, BulkGridWithRoute::class);

        self::assertSame('id', $rowIdProperty);
    }

    /**
     * @param \ReflectionClass<object> $ref
     */
    private function readAsGrid(\ReflectionClass $ref): AsGrid
    {
        $attributes = $ref->getAttributes(AsGrid::class);
        self::assertNotEmpty($attributes, 'Fixture must carry #[AsGrid].');

        return $attributes[0]->newInstance();
    }
}
