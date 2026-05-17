<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;

#[AsGrid(
    name: 'fixture_sortable_on_render_row',
    dataSource: InMemoryDataSource::class,
    filterClass: DummyFilter::class,
    renderRow: 'some/template.html.twig',
    defaultSort: 'code:asc',
)]
final class GridWithSortableOnRenderRow
{
    #[Column(label: 'Code', sortable: 'code')]
    public string $code;
}
