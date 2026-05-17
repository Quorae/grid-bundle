<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\RowId;

/**
 * Invalid row DTO — two `#[RowId]` attributes on the same class. The
 * resolver must reject the owning grid.
 */
final readonly class BulkRowDtoWithTwoRowIds
{
    public function __construct(
        #[RowId]
        public int $pk,
        #[RowId]
        public string $code,
    ) {
    }
}
