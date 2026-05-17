<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

/**
 * Row DTO that exposes no `id` and no `#[RowId]` — must cause
 * `GridDefinitionResolver` to reject the grid when combined with any
 * `#[BulkAction]`.
 */
final readonly class BulkRowDtoWithoutId
{
    public function __construct(
        public string $label,
    ) {
    }
}
