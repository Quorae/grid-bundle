<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Search;

#[AsGrid(name: 'fixture_duplicate_search', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class DuplicateSearchGrid
{
    #[Search(fields: ['code'])]
    public ?string $q = null;

    #[Search(fields: ['label'])]
    public ?string $q2 = null;

    #[Column(label: 'Code')]
    public string $code;
}
