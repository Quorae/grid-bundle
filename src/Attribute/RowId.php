<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

/**
 * Marks the property on a row DTO that carries the scalar identifier used by
 * bulk-action selection.
 *
 * When a grid declares at least one {@see BulkAction}, the row DTO must
 * expose exactly one `#[RowId]` property — or rely on the fallback
 * convention (`public int|string $id`). At most one `#[RowId]` is allowed
 * per DTO.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class RowId
{
}
