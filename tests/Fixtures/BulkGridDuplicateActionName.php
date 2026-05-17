<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Invalid — two bulk actions share the same name.
 */
#[AsGrid(
    name: 'fixture_bulk_dup_name',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(name: 'delete', label: 'A', handler: BulkActionStubHandler::class, ownershipValidator: StubOwnershipValidator::class)]
#[BulkAction(name: 'delete', label: 'B', handler: BulkActionStubHandler::class, ownershipValidator: StubOwnershipValidator::class)]
final class BulkGridDuplicateActionName
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
