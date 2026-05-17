<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

use Quorae\GridBundle\Enum\LedgerSignature;

/**
 * Attached to a **static method** whose return type is `bool`, which receives
 * each row and decides whether the given Ledger signature applies to the
 * resulting `<tr>` (adds a CSS class in the Twig component layer).
 *
 * Multiple `#[RowSignature]` methods may coexist on a single grid, each
 * binding a different `LedgerSignature`.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class RowSignature
{
    public function __construct(
        public LedgerSignature $signature,
    ) {
    }
}
