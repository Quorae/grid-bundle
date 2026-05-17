<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

/**
 * Aggregate returned by a {@see \Quorae\GridBundle\Contract\BulkActionHandler}.
 *
 * - `$successCount` / `$failureCount` : integers to drive flash summaries
 *   without leaking domain nuance ("3 mémos supprimés", "1 écriture en
 *   erreur").
 * - `$errors` : per-row reasons, surfaced as secondary flashes so the user
 *   knows which rows of the selection were skipped.
 * - `$successMessage` : optional summary sentence — the caller (LiveGrid)
 *   falls back to a default when empty.
 */
final readonly class BulkActionResult
{
    /**
     * @param list<BulkActionItemError> $errors
     */
    public function __construct(
        public int $successCount,
        public int $failureCount,
        public array $errors,
        public string $successMessage = '',
    ) {
    }
}
