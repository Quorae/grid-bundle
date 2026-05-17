<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_duplicate_sortable_key',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    defaultSort: 'code:asc',
)]
final class GridWithDuplicateSortableKey
{
    #[Column(label: 'Code', sortable: 'code')]
    public string $code;

    #[Column(label: 'Libellé', sortable: 'code')]
    public string $label;
}
