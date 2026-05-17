<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Invalid — ownership validator does not implement BulkOwnershipValidator.
 */
#[AsGrid(
    name: 'fixture_bulk_bad_validator',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    handler: BulkActionStubHandler::class,
    // @phpstan-ignore argument.type (deliberately passing a non-validator class to exercise the resolver contract check)
    ownershipValidator: NotAValidator::class,
)]
final class BulkGridValidatorMissingInterface
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
