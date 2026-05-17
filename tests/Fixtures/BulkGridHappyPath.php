<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_bulk_happy',
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
    icon: 'heroicons:trash-16-solid',
    confirmMessage: 'Supprimer {count} éléments ?',
)]
#[BulkAction(
    name: 'archive',
    label: 'Archiver',
    handler: BulkActionStubHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
)]
final class BulkGridHappyPath
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
