<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Invalid — row DTO carries two `#[RowId]` attributes.
 */
#[AsGrid(
    name: 'fixture_bulk_dup_rowid',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDtoWithTwoRowIds::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
)]
final class BulkGridDuplicateRowId
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
