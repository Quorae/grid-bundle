<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_sortable_no_default',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
)]
final class GridWithSortableNoDefault
{
    #[Column(label: 'Code', sortable: 'code')]
    public string $code;
}
