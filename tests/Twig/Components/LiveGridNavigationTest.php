<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Twig\Components;

use Quorae\GridBundle\Twig\Components\LiveGrid;
use PHPUnit\Framework\TestCase;

/**
 * A raw `data-model` write on `$q` / `$criteria` / `$sort` bypasses the
 * setFilter / clearFilters / sort Live actions, so pagination was never
 * reset and a filtered query stayed on a stale page. `onFilterChanged`
 * must restart at page 1; the `$page` hook must NOT, or pagination breaks.
 */
final class LiveGridNavigationTest extends TestCase
{
    private function liveGrid(): LiveGrid
    {
        // Hooks use no constructor collaborators — skip the final-typed deps.
        return (new \ReflectionClass(LiveGrid::class))->newInstanceWithoutConstructor();
    }

    public function testFilterChangeResetsToPageOneAndClearsSelection(): void
    {
        $grid = $this->liveGrid();
        $grid->page = 4;
        $grid->selectedIds = [11, 22];
        $grid->expandedRowId = '7';

        $grid->onFilterChanged();

        self::assertSame(1, $grid->page);
        self::assertSame([], $grid->selectedIds);
        self::assertNull($grid->expandedRowId);
    }

    public function testPageChangeKeepsThePageAndOnlyClearsSelection(): void
    {
        $grid = $this->liveGrid();
        $grid->page = 4;
        $grid->selectedIds = [11, 22];
        $grid->expandedRowId = '7';

        $grid->resetSelectionOnNavigation();

        self::assertSame(4, $grid->page, 'pagination must survive a page change');
        self::assertSame([], $grid->selectedIds);
        self::assertNull($grid->expandedRowId);
    }
}
