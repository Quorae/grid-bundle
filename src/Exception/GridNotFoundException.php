<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Exception;

/**
 * Raised when a controller asks the {@see \Quorae\GridBundle\Registry\GridRegistry}
 * for a name that no `#[AsGrid]` class carries.
 */
final class GridNotFoundException extends \DomainException implements GridExceptionInterface
{
    public static function byName(string $name): self
    {
        return new self(\sprintf('No grid registered under the name "%s".', $name));
    }
}
