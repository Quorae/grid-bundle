<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Dto;

use Quorae\GridBundle\Contract\BulkActor;

/**
 * Input passed from the Live Component to a
 * {@see \Quorae\GridBundle\Contract\BulkActionHandler}.
 *
 * - `$gridName` + `$actionName` anchor the invocation back to the
 *   declarative attributes — handlers that serve several grids can pivot on
 *   the name.
 * - `$rowIds` is the set of ids the user selected in the grid, **already
 *   filtered by the {@see \Quorae\GridBundle\Contract\BulkOwnershipValidator}**
 *   registered on the bulk action. Every ID in this list belongs to the
 *   current scope — handlers do not need to re-check ownership.
 * - `$extraContext` mirrors the render-time context — typically carries the
 *   host aggregate the grid was built for.
 * - `$actor` is the currently authenticated principal — the handler never
 *   re-queries the security context.
 */
final readonly class BulkActionRequest
{
    /**
     * @param list<int|string>     $rowIds
     * @param array<string, mixed> $extraContext
     */
    public function __construct(
        public string $gridName,
        public string $actionName,
        public array $rowIds,
        public array $extraContext,
        public BulkActor $actor,
    ) {
    }
}
