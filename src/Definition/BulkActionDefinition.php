<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;

/**
 * Compile-time description of a {@see \Quorae\GridBundle\Attribute\BulkAction}.
 *
 * Held by {@see GridDefinition::$bulkActions} and consumed both by the
 * LiveGrid Twig shell (to render the bulk-action bar) and by the
 * {@see \Quorae\GridBundle\Twig\Components\LiveGrid::executeBulk()} Live action
 * (to resolve the handler service + enforce the required role).
 */
final readonly class BulkActionDefinition
{
    /**
     * @param ?class-string<BulkActionHandler>      $handlerService     null for route-based actions
     * @param ?class-string<BulkOwnershipValidator> $ownershipValidator null for route-based actions
     * @param ?string                               $route              Symfony route name for navigation-based actions (mutually exclusive with $handlerService)
     */
    public function __construct(
        public string $name,
        public string $label,
        public ?string $handlerService,
        public ?string $ownershipValidator,
        public bool $destructive,
        public ?string $icon,
        public ?string $confirmMessage,
        public string $requiredRole,
        public ?string $route = null,
    ) {
    }
}
