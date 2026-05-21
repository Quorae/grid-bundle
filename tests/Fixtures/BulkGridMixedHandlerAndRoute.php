<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Grid with two bulk actions: one handler-based, one route-based.
 * Both should coexist on the same grid.
 */
#[AsGrid(
    name: 'fixture_bulk_mixed',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
    destructive: true,
)]
#[BulkAction(
    name: 'batch_remediation',
    label: 'Remédier',
    route: 'app_batch_remediation',
)]
final class BulkGridMixedHandlerAndRoute
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
