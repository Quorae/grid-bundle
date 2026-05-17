<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\RowSignature;
use Quorae\GridBundle\Enum\LedgerSignature;

#[AsGrid(name: 'fixture_non_static_row_signature', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class NonStaticRowSignatureGrid
{
    #[Column(label: 'Code')]
    public string $code;

    #[RowSignature(LedgerSignature::AnomalyBar)]
    public function notStatic(object $row): bool
    {
        return false;
    }
}
