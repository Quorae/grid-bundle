<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Exception;

/**
 * Marker interface for all Grid framework exceptions.
 *
 * Each concrete exception keeps its SPL base (\LogicException,
 * \RuntimeException, \DomainException) so the PHP exception hierarchy
 * stays meaningful; catchers can use this interface to distinguish
 * Grid exceptions from generic PHP ones.
 */
interface GridExceptionInterface extends \Throwable
{
}
