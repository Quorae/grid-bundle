<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

/**
 * Pagination strategy a grid can declare.
 *
 * `PrevNext` — default : cheap prev/next buttons, no `COUNT(*)`. The data
 * source is expected to over-fetch (`limit + 1`) and return `hasNext`
 * directly. Appropriate for ACD (MyISAM `COUNT(*)` is unreasonably slow
 * on large tables).
 *
 * `Offset` — page numbers 1..N with a total count. Only appropriate when
 * the data source can count efficiently (App DB on indexed columns,
 * small result sets). Never use on ACD.
 */
enum Pagination: string
{
    case PrevNext = 'prev_next';
    case Offset = 'offset';
}
