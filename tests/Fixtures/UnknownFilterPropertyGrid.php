<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Filter;
use Quorae\GridBundle\Enum\FilterType;

#[AsGrid(name: 'fixture_unknown_property', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class UnknownFilterPropertyGrid
{
    // `unknownField` is not declared on DummyFilter → resolver should throw.
    #[Filter(type: FilterType::Toggle)]
    public bool $unknownField = false;

    #[Column(label: 'Code')]
    public string $code;
}
