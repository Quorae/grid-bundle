<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

/**
 * Row DTO exposing a public `int $id` — covered by the `GridDefinitionResolver`
 * convention fallback (no `#[RowId]` needed).
 */
final readonly class BulkRowDto
{
    public function __construct(
        public int $id,
        public string $label,
    ) {
    }
}
