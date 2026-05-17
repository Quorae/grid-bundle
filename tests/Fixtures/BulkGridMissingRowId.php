<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Invalid — row DTO has neither `#[RowId]` nor a public `id` property.
 */
#[AsGrid(
    name: 'fixture_bulk_missing_row_id',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDtoWithoutId::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
)]
final class BulkGridMissingRowId
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
