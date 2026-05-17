<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Enum\LedgerSignature;

/**
 * Binds a `#[RowSignature]`-decorated static method to its Ledger signature.
 *
 * `callable` is the fully-qualified static callable `[$class, $method]` —
 * the resolver proves the method exists and is static before constructing.
 */
final readonly class RowSignatureDefinition
{
    /**
     * @param array{0: class-string, 1: string} $callable
     */
    public function __construct(
        public LedgerSignature $signature,
        public array $callable,
    ) {
    }
}
