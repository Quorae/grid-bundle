<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Search;

#[AsGrid(name: 'fixture_bad_search_type', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class BadSearchTypeGrid
{
    #[Search(fields: ['code'])]
    public int $q = 0;

    #[Column(label: 'Code')]
    public string $code;
}
