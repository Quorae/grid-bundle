<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\RowId;

/**
 * Row DTO whose scalar id lives on a property other than `id` — exercises
 * the `#[RowId]` opt-in path.
 */
final readonly class BulkRowDtoWithRowIdAttribute
{
    public function __construct(
        #[RowId]
        public string $publicCode,
        public string $label,
    ) {
    }
}
