<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Contract;

use Quorae\GridBundle\Dto\BulkActionRequest;
use Quorae\GridBundle\Dto\BulkActionResult;

/**
 * Server-side contract fulfilled by every service behind a
 * {@see \Quorae\GridBundle\Attribute\BulkAction}.
 *
 * Implementations are typed as `final readonly class` and invoked by the
 * {@see \Quorae\GridBundle\Registry\BulkActionHandlerRegistry} after Live-Component
 * authorisation. Errors are surfaced through {@see BulkActionResult::$errors}
 * (one entry per row that failed) rather than by throwing — throwing leaves
 * the whole selection in an indeterminate state.
 */
interface BulkActionHandler
{
    public function __invoke(BulkActionRequest $request): BulkActionResult;
}
