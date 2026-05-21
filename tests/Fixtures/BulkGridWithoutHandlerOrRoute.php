<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_bulk_no_handler_no_route',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'orphan',
    label: 'Orpheline',
)]
final class BulkGridWithoutHandlerOrRoute
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
