<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Definition;

use Quorae\GridBundle\Definition\ColumnDefinition;
use Quorae\GridBundle\Definition\SortableValidator;
use Quorae\GridBundle\Dto\SortOrder;
use Quorae\GridBundle\Enum\Formatter;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the extracted {@see SortableValidator} — covers sortable-key
 * collection, renderRow mutual exclusion, and defaultSort parsing.
 */
final class SortableValidatorTest extends TestCase
{
    private SortableValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SortableValidator();
    }

    // --- collectSortableKeys ---

    public function testCollectsSortableKeysFromColumns(): void
    {
        $columns = [
            $this->column('code', sortable: 'code'),
            $this->column('label', sortable: false),
            $this->column('amount', sortable: 'amount'),
        ];

        $keys = $this->validator->collectSortableKeys('TestGrid', $columns);

        self::assertSame(['code' => true, 'amount' => true], $keys);
    }

    public function testCollectsEmptyKeysWhenNoSortable(): void
    {
        $columns = [
            $this->column('code', sortable: false),
        ];

        $keys = $this->validator->collectSortableKeys('TestGrid', $columns);

        self::assertSame([], $keys);
    }

    public function testRejectsDuplicateSortableKey(): void
    {
        $columns = [
            $this->column('code', sortable: 'code'),
            $this->column('altCode', sortable: 'code'),
        ];

        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('declares the sortable key "code" on more than one column');

        $this->validator->collectSortableKeys('TestGrid', $columns);
    }

    // --- assertRenderRowAndSortableAreMutuallyExclusive ---

    public function testAcceptsRenderRowWithoutSortable(): void
    {
        // Should not throw
        $this->validator->assertRenderRowAndSortableAreMutuallyExclusive('TestGrid', true, false);

        $this->addToAssertionCount(1);
    }

    public function testAcceptsNonRenderRowWithSortable(): void
    {
        // Should not throw
        $this->validator->assertRenderRowAndSortableAreMutuallyExclusive('TestGrid', false, true);

        $this->addToAssertionCount(1);
    }

    public function testRejectsRenderRowWithSortable(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('combines renderRow with sortable columns');

        $this->validator->assertRenderRowAndSortableAreMutuallyExclusive('TestGrid', true, true);
    }

    // --- parseDefaultSort ---

    public function testParsesValidDefaultSort(): void
    {
        $sort = $this->validator->parseDefaultSort('TestGrid', 'code:asc', ['code' => true]);

        self::assertInstanceOf(SortOrder::class, $sort);
        self::assertSame('code', $sort->column);
        self::assertSame('asc', $sort->direction);
    }

    public function testParsesDescendingDefaultSort(): void
    {
        $sort = $this->validator->parseDefaultSort('TestGrid', 'amount:desc', ['amount' => true]);

        self::assertNotNull($sort);
        self::assertSame('amount', $sort->column);
        self::assertSame('desc', $sort->direction);
    }

    public function testReturnsNullWhenNoSortableAndNoDefault(): void
    {
        $sort = $this->validator->parseDefaultSort('TestGrid', null, []);

        self::assertNull($sort);
    }

    public function testRejectsDefaultSortRequiredWhenSortableExist(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('declares sortable columns but no #[AsGrid(defaultSort');

        $this->validator->parseDefaultSort('TestGrid', null, ['code' => true]);
    }

    public function testRejectsMalformedDefaultSort(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('expected the form "<key>:asc" or "<key>:desc"');

        $this->validator->parseDefaultSort('TestGrid', 'noColonHere', ['code' => true]);
    }

    public function testRejectsInvalidDirection(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('expected the form "<key>:asc" or "<key>:desc"');

        $this->validator->parseDefaultSort('TestGrid', 'code:up', ['code' => true]);
    }

    public function testRejectsUnknownColumn(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('defaultSort on column "nope"');

        $this->validator->parseDefaultSort('TestGrid', 'nope:asc', ['code' => true]);
    }

    public function testAllowsUnknownColumnForRenderRowGrid(): void
    {
        $sort = $this->validator->parseDefaultSort('TestGrid', 'custom:desc', [], true);

        self::assertNotNull($sort);
        self::assertSame('custom', $sort->column);
        self::assertSame('desc', $sort->direction);
    }

    private function column(string $propertyName, string|false $sortable): ColumnDefinition
    {
        return new ColumnDefinition(
            propertyName: $propertyName,
            label: ucfirst($propertyName),
            class: null,
            formatter: Formatter::Plain,
            template: null,
            sortable: $sortable,
            hideOnMobile: false,
        );
    }
}
