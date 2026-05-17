<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_bulk_row_id_attr',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDtoWithRowIdAttribute::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
)]
final class BulkGridWithRowIdAttribute
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
