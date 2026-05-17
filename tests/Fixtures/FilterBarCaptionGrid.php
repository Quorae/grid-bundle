<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Filter;
use Quorae\GridBundle\Enum\FilterType;

#[AsGrid(name: 'fixture_filter_bar_caption', dataSource: InMemoryDataSource::class, filterClass: DummyFilter::class)]
final class FilterBarCaptionGrid
{
    #[Filter(
        type: FilterType::Pills,
        label: 'Classe',
        choices: [1, 2, 3],
        caption: 'Filtrer par classe comptable',
        valueMonospace: true,
        group: 'comptabilité',
    )]
    public ?int $classe = null;

    #[Filter(type: FilterType::Toggle)]
    public bool $revisedOnly = false;

    #[Column(label: 'Code')]
    public string $code;
}
