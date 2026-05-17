<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Invalid — declares a bulk action without `interactive: true`. Must be
 * rejected by the resolver.
 */
#[AsGrid(
    name: 'fixture_bulk_not_interactive',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: false,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
)]
final class BulkGridInteractiveFalse
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
