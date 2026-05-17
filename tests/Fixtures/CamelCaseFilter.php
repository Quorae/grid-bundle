<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Fixtures;

/**
 * Filter DTO whose constructor declares multi-word camelCase parameters —
 * mirrors the real `InvoicesFilter` / `TimelineFilter` shape where a
 * `#[Filter(type: DateRange)]` on grid property `date` emits the query-string
 * keys `criteria[date_from]` / `criteria[date_to]` while the DTO parameters
 * are `dateFrom` / `dateTo`.
 *
 * `clientName` is a non-date multi-word prop : it proves the snake_case ⇄
 * camelCase bridge is general, not a date-specific hack. `q` and `classe`
 * stay single-word to guard against regressions on the existing exact-match
 * path.
 */
final class CamelCaseFilter
{
    public function __construct(
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $clientName = null,
        public ?string $q = null,
        public ?int $classe = null,
    ) {
    }
}
