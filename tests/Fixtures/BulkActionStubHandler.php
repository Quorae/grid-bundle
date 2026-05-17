<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Dto\BulkActionRequest;
use Quorae\GridBundle\Dto\BulkActionResult;

/**
 * No-op handler used by every bulk-action fixture grid. Implements the
 * interface so the resolver accepts it ; returns an empty result.
 */
final readonly class BulkActionStubHandler implements BulkActionHandler
{
    public function __invoke(BulkActionRequest $request): BulkActionResult
    {
        return new BulkActionResult(successCount: 0, failureCount: 0, errors: []);
    }
}
