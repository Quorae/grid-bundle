<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

/**
 * Deliberately loose filter DTO used by the unit-test fixtures. Lets the
 * framework hydrate every kind of scalar type without ACD quirks.
 */
final class DummyFilter
{
    public function __construct(
        public ?string $q = null,
        public ?int $classe = null,
        public bool $revisedOnly = false,
        public ?DummyStatus $status = null,
        public ?int $clientId = null,
    ) {
    }
}
