<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

/**
 * Per-row failure reported by a {@see \Quorae\GridBundle\Contract\BulkActionHandler}.
 *
 * The row identifier is preserved so the Live Component can flash a
 * contextual warning ("mémo #42 introuvable", "écriture #13 verrouillée")
 * without forcing the handler to know the UI vocabulary.
 */
final readonly class BulkActionItemError
{
    public function __construct(
        public int|string $rowId,
        public string $reason,
    ) {
    }
}
