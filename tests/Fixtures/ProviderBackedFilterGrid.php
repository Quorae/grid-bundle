<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Filter;
use Quorae\GridBundle\Enum\FilterType;

#[AsGrid(name: 'fixture_provider_backed', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class ProviderBackedFilterGrid
{
    #[Filter(type: FilterType::Select, label: 'Classe', choicesProvider: ClasseChoicesProvider::class)]
    public ?int $classe = null;

    #[Column(label: 'Code')]
    public string $code;
}
