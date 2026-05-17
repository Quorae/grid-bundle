<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

/**
 * Widget used to render a filter in the grid's filter bar.
 *
 * The framework produces a neutral description ; the Twig component layer
 * decides the actual DOM (pills, select, toggle, date-range inputs).
 */
enum FilterType: string
{
    case Pills = 'pills';
    case Select = 'select';
    case Toggle = 'toggle';
    case DateRange = 'date_range';
}
