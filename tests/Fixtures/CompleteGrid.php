<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\Filter;
use Quorae\GridBundle\Attribute\RowSignature;
use Quorae\GridBundle\Attribute\Search;
use Quorae\GridBundle\Enum\FilterType;
use Quorae\GridBundle\Enum\Formatter;
use Quorae\GridBundle\Enum\LedgerSignature;
use Quorae\GridBundle\Enum\Pagination;
use Quorae\GridBundle\Enum\SearchMode;

#[AsGrid(
    name: 'fixture_complete',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    pagination: Pagination::PrevNext,
    perPage: 25,
    interactive: false,
    emptyMessage: 'Rien ici.',
    defaultSort: 'code:asc',
)]
final class CompleteGrid
{
    #[Search(fields: ['code', 'label'], placeholder: 'Filtrer…', mode: SearchMode::Contains, debounceMs: 400)]
    public ?string $q = null;

    #[Filter(type: FilterType::Pills, label: 'Classe', choices: [1, 2, 3])]
    public ?int $classe = null;

    #[Filter(type: FilterType::Toggle)]
    public bool $revisedOnly = false;

    #[Column(label: 'Code', sortable: 'code')]
    public string $code;

    #[Column(label: 'Libellé', class: 'lbl')]
    public string $label;

    #[Column(
        label: 'Solde',
        class: 'num',
        formatter: Formatter::Badge,
        hideOnMobile: true,
    )]
    public string $solde;

    #[RowSignature(LedgerSignature::AnomalyBar)]
    public static function hasAnomaly(object $row): bool
    {
        return property_exists($row, 'anomaly') && $row->anomaly === true;
    }
}
