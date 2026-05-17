<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

/**
 * Rendering strategy applied to a column cell.
 *
 * Every case maps to a Twig partial under `templates/components/Grid/cell/`
 * (rendered by the `Grid` Twig component layer — not this backend module).
 */
enum Formatter: string
{
    case Plain = 'plain';
    case MontantFr = 'montant_fr';
    case DateFr = 'date_fr';
    case Chip = 'chip';
    case Badge = 'badge';
    case Twig = 'twig';
}
