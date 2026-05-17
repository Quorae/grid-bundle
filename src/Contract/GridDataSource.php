<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Contract;

use Quorae\GridBundle\Dto\GridResponse;
use Quorae\GridBundle\Dto\Page;

/**
 * Contract fulfilled by every backend that feeds a grid — typically an
 * existing Repository which adds `implements GridDataSource` without
 * changing its native methods.
 *
 * The `$filter` parameter is a framework-hydrated instance of the concrete
 * filter DTO (`BalanceFilter`, `GrandLivreFilter`, etc.). PHP forbids
 * narrowing the parameter type on an interface method (LSP contravariance),
 * so the signature stays `object $filter`. Implementations assert the
 * expected type at runtime :
 *
 * ```php
 * public function fetch(object $filter, Page $page): GridResponse
 * {
 *     if (!$filter instanceof BalanceFilter) {
 *         throw new \InvalidArgumentException(...);
 *     }
 *     // …
 * }
 * ```
 *
 * The data source is sovereign on its SQL — the framework never produces a
 * query. It only hands a hydrated DTO and expects a page back.
 *
 * **Row identity (mixed-PK).** The framework never inspects the type of a
 * row's id : `#[RowId]` / `#[RowLink]` carry *property names*, not values,
 * and `BulkActionItemError::$rowId` / `BulkActionRequest::$rowIds` are
 * already `int|string`. A host id may therefore be `int`, `string`, or a
 * `\Stringable` value object (e.g. `Symfony\Component\Uid\Uuid`). The data
 * source is responsible for emitting a **scalar or `\Stringable`** id on the
 * row DTO it returns — typically a concretely-typed `public int $id` or
 * `public string $id` populated via `(string) $entity->getId()` in the
 * `fetch()` map step. The framework imposes no constraint beyond "the row
 * exposes the id property the grid declares".
 */
interface GridDataSource
{
    public function fetch(object $filter, Page $page): GridResponse;
}
