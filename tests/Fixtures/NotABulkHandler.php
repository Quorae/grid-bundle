<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

/**
 * Fixture class that does NOT implement `BulkActionHandler` — used to
 * verify the resolver rejects grids referencing an incompatible handler.
 */
final readonly class NotABulkHandler
{
}
