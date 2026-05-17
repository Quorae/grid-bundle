<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Contract;

/**
 * Centralized ownership guard for bulk actions — filters out row IDs that
 * do not belong to the current scope (dossier, client, tenant…).
 *
 * Registered on each {@see \Quorae\GridBundle\Attribute\BulkAction} and invoked by
 * the {@see \Quorae\GridBundle\Handler\BulkActionExecutor} *before* the handler
 * receives the request. This makes ownership validation structural rather
 * than relying on each handler to remember the check.
 */
interface BulkOwnershipValidator
{
    /**
     * @param list<int|string>     $rowIds       IDs submitted by the client (untrusted)
     * @param array<string, mixed> $extraContext render-time context (typically carries dossierId, clientId…)
     *
     * @return list<int|string> subset of $rowIds that belong to the current scope
     */
    public function filterOwned(array $rowIds, array $extraContext): array;
}
