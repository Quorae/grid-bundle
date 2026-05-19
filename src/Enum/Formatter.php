<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

/**
 * Built-in rendering strategies for column cells.
 *
 * Every case maps to a Twig partial under `templates/components/Grid/cell/`.
 * Domain-specific formatters (money, locale dates…) should use `Twig` with
 * a host-project template — the bundle stays locale/domain neutral.
 */
enum Formatter: string
{
    case Plain = 'plain';
    case Chip = 'chip';
    case Badge = 'badge';
    case Twig = 'twig';
}
