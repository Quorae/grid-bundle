<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_bad_direction_default_sort',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    defaultSort: 'code:sideways',
)]
final class GridWithDefaultSortBadDirection
{
    #[Column(label: 'Code', sortable: 'code')]
    public string $code;
}
