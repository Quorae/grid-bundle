<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(name: 'fixture_minimal', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class MinimalGrid
{
    #[Column(label: 'Code')]
    public string $code;
}
