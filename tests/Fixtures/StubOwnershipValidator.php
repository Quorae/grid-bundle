<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Contract\BulkOwnershipValidator;

/**
 * Pass-through validator — returns all IDs unchanged. Used by fixtures
 * where ownership is not under test.
 */
final readonly class StubOwnershipValidator implements BulkOwnershipValidator
{
    public function filterOwned(array $rowIds, array $extraContext): array
    {
        return $rowIds;
    }
}
