<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Dto\SortOrder;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;

/**
 * Validates and parses sortable-column constraints on a grid definition.
 *
 * Extracted from {@see GridDefinitionResolver} so the resolver focuses on
 * assembling the definition while sort-related invariants live here.
 *
 * Instantiated via `new` at compile-time — no DI, no state.
 */
final class SortableValidator
{
    /**
     * @param list<ColumnDefinition> $columns
     *
     * @return array<string, true>
     */
    public function collectSortableKeys(string $gridClass, array $columns): array
    {
        $keys = [];
        foreach ($columns as $column) {
            if ($column->sortable === false) {
                continue;
            }
            if (isset($keys[$column->sortable])) {
                throw InvalidGridDefinitionException::duplicateSortableKey($gridClass, $column->sortable);
            }
            $keys[$column->sortable] = true;
        }

        return $keys;
    }

    public function assertRenderRowAndSortableAreMutuallyExclusive(string $gridClass, bool $isRenderRow, bool $hasSortableKeys): void
    {
        if ($isRenderRow && $hasSortableKeys) {
            throw InvalidGridDefinitionException::sortableOnRenderRowGrid($gridClass);
        }
    }

    /**
     * @param array<string, true> $sortableKeys
     */
    public function parseDefaultSort(string $gridClass, ?string $raw, array $sortableKeys, bool $isRenderRowGrid = false): ?SortOrder
    {
        if ($raw === null) {
            if ($sortableKeys === []) {
                return null;
            }
            throw InvalidGridDefinitionException::defaultSortRequired($gridClass);
        }

        $parts = explode(':', $raw, 2);
        if (\count($parts) !== 2) {
            throw InvalidGridDefinitionException::defaultSortMalformed($gridClass, $raw);
        }
        [$column, $direction] = $parts;
        if ($direction !== 'asc' && $direction !== 'desc') {
            throw InvalidGridDefinitionException::defaultSortMalformed($gridClass, $raw);
        }
        // renderRow grids delegate sorting entirely to the repository — no
        // column-based sortable validation needed.
        if (!$isRenderRowGrid && !isset($sortableKeys[$column])) {
            throw InvalidGridDefinitionException::defaultSortReferencesUnknownColumn($gridClass, $column);
        }

        return new SortOrder(column: $column, direction: $direction);
    }
}
