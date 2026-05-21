<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_bulk_handler_and_route',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'conflict',
    label: 'Conflit',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
    route: 'app_some_route',
)]
final class BulkGridWithHandlerAndRoute
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
