<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\RowLink;

#[AsGrid(
    name: 'fixture_expandable_row_link',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    expandable: true,
    expandRoute: 'test_expand_route',
)]
#[RowLink(route: 'app_show')]
final class ExpandableWithRowLinkGrid
{
    #[Column(label: 'Code')]
    public string $code = '';
}
