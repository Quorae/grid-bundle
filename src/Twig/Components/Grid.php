<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Twig\Components;

use Quorae\GridBundle\Dto\GridView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Twig component — renders a grid in **Turbo Frame** mode (stateless :
 * every filter / pagination / sort change is a full request whose state
 * lives in the URL query string).
 *
 * No business logic here — the component is a pass-through to the template,
 * which reads everything it needs from `$view`.
 *
 * Template path : `@QuoraeGrid/components/Grid/grid.html.twig` (WP-3 — frontend task).
 */
#[AsTwigComponent(name: 'Grid:Grid', template: '@QuoraeGrid/components/Grid/grid.html.twig')]
final class Grid
{
    public GridView $view;
}
