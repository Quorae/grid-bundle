<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Dto\Page;
use Quorae\GridBundle\Dto\SortOrder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Parses pagination and sort parameters from the HTTP request for a grid.
 *
 * Responsibilities:
 *  - Read `?p=N` and clamp to [1, MAX_PAGE_NUMBER]
 *  - Read `?sort=<column>:<direction>` and validate against declared sortable columns
 *  - Fall back to the grid's `defaultSort` when user sort is invalid or absent
 *
 * Extracted from {@see RenderGridHandler} to keep the handler focused on
 * pipeline orchestration.
 */
final readonly class PageParser
{
    /** Query-string parameter name for the current page number. */
    public const string PAGE_PARAM = 'p';

    /** Upper bound for `?p=N` — guards against arithmetic overflow. */
    private const int MAX_PAGE_NUMBER = 1_000_000;

    public function parse(GridDefinition $definition, Request $request): Page
    {
        $query = $request->query;

        $pageRaw = $query->get(self::PAGE_PARAM);
        $pageNumber = 1;
        if (\is_string($pageRaw) && filter_var($pageRaw, \FILTER_VALIDATE_INT) !== false) {
            $candidate = (int) $pageRaw;
            $pageNumber = max(1, min($candidate, self::MAX_PAGE_NUMBER));
        }

        $sort = $this->parseSortOrder($definition, $query->get('sort'));

        return new Page(number: $pageNumber, limit: $definition->perPage, sort: $sort);
    }

    private function parseSortOrder(GridDefinition $definition, mixed $rawSort): ?SortOrder
    {
        $parsed = $this->parseRawSort($definition, $rawSort);

        return $parsed ?? $definition->defaultSort;
    }

    private function parseRawSort(GridDefinition $definition, mixed $rawSort): ?SortOrder
    {
        if (!\is_string($rawSort) || $rawSort === '') {
            return null;
        }
        $parts = explode(':', $rawSort, 2);
        if (\count($parts) !== 2) {
            return null;
        }
        [$column, $direction] = $parts;
        $direction = strtolower($direction);
        if ($direction !== 'asc' && $direction !== 'desc') {
            return null;
        }

        $sortableColumn = $this->findSortableColumnSqlName($definition, $column);
        if ($sortableColumn === null) {
            return null;
        }

        return new SortOrder(column: $sortableColumn, direction: $direction);
    }

    private function findSortableColumnSqlName(GridDefinition $definition, string $columnCandidate): ?string
    {
        foreach ($definition->columns as $column) {
            if ($column->sortable === false) {
                continue;
            }
            if ($column->sortable === $columnCandidate || $column->propertyName === $columnCandidate) {
                return $column->sortable;
            }
        }

        return null;
    }
}
