<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_bulk_route',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'batch_remediation',
    label: 'Remédier la sélection',
    route: 'app_batch_remediation_selector',
    icon: 'heroicons:wrench-16-solid',
)]
final class BulkGridWithRoute
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
