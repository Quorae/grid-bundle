<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_expandable_no_route',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    expandable: true,
)]
final class ExpandableWithoutRouteGrid
{
    #[Column(label: 'Code')]
    public string $code = '';
}
