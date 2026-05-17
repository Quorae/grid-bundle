<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Enum\Pagination;

/**
 * Offset-paginated grid backed by {@see PaginatedDataSource} — drives the
 * handler's out-of-range page clamp test (spec §9). `perPage` is small so a
 * modest fixture row count yields several pages.
 */
#[AsGrid(
    name: 'fixture_offset_paginated',
    dataSource: PaginatedDataSource::class,
    filterClass: CamelCaseFilter::class,
    pagination: Pagination::Offset,
    perPage: 10,
)]
final class OffsetPaginatedGrid
{
    #[Column(label: 'Code')]
    public string $code;
}
