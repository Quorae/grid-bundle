<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\Column;

/**
 * Invalid — handler class does not implement `BulkActionHandler`. The
 * resolver must detect and reject this at compile-time. The `handler`
 * parameter is typed as `class-string<BulkActionHandler>` on the attribute,
 * so we cannot use the typed fixture handler here — we pass the raw FQCN
 * string of a class that the resolver will then reject via reflection.
 */
#[AsGrid(
    name: 'fixture_bulk_bad_handler',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    interactive: true,
    rowClass: BulkRowDto::class,
)]
#[BulkAction(
    name: 'delete',
    label: 'Supprimer',
    // @phpstan-ignore argument.type (deliberately passing a non-handler class to exercise the resolver contract check)
    handler: NotABulkHandler::class,
    ownershipValidator: StubOwnershipValidator::class,
)]
final class BulkGridHandlerMissingInterface
{
    #[Column(label: 'Libellé')]
    public string $label = '';
}
