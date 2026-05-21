<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;

/**
 * Declares a bulk action available on a grid — a button rendered above the
 * table that, once a user has checked ≥ 1 row, invokes a server-side
 * {@see BulkActionHandler} on the selected ids.
 *
 * Repeatable — a grid can declare N bulk actions (delete, archive, export…),
 * each with its own handler and access role.
 *
 * Side-effects of declaring `#[BulkAction]` on a grid class :
 *  - the grid **must** be rendered as a Live Component (`interactive: true`)
 *  - the row DTO **must** expose a scalar id — either by convention
 *    (`public int|string $id`) or by tagging a different property with
 *    {@see RowId}
 *  - the handler class must exist as a service and implement
 *    {@see BulkActionHandler}
 *  - the `ownershipValidator` class must exist and implement
 *    {@see BulkOwnershipValidator} — enforced at compile-time
 *
 * Discovered at compile-time by {@see \Quorae\GridBundle\Definition\GridDefinitionResolver}.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class BulkAction
{
    /**
     * @param ?class-string<BulkActionHandler>      $handler            FQCN of a service that implements BulkActionHandler — mutually exclusive with $route
     * @param ?class-string<BulkOwnershipValidator> $ownershipValidator FQCN of a service that filters IDs by ownership — required when $handler is set
     * @param ?string                               $route              Symfony route name — when set, the bulk button navigates to this route with selectedIds as query params instead of invoking a handler
     * @param ?string                               $icon               Heroicons identifier (e.g. `heroicons:trash-16-solid`) rendered in the action button
     * @param ?string                               $confirmMessage     optional client-side prompt ; the placeholder `{count}` is substituted with the current selection size
     * @param string                                $requiredRole       Symfony role the current user must hold — enforced server-side before invoking the handler
     */
    public function __construct(
        public string $name,
        public string $label,
        public ?string $handler = null,
        public ?string $ownershipValidator = null,
        public ?string $route = null,
        public bool $destructive = false,
        public ?string $icon = null,
        public ?string $confirmMessage = null,
        public string $requiredRole = 'ROLE_USER',
    ) {
    }
}
