<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;

#[AsGrid(name: 'fixture_no_columns', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class NoColumnsGrid
{
}
