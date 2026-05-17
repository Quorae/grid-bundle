<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_expandable',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    expandable: true,
    expandRoute: 'test_expand_route',
    expandRouteParam: 'rowId',
)]
final class ExpandableGrid
{
    #[Column(label: 'Code')]
    public string $code = '';
}
