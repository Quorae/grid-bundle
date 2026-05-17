<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

/**
 * Deliberately does NOT implement BulkOwnershipValidator — used to test
 * compile-time rejection by the BulkActionReader.
 */
final class NotAValidator
{
}
