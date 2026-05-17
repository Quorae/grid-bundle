<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

/**
 * Declarative hint passed to the data source about how the search string
 * should be interpreted. The repository is free to implement each mode as
 * it sees fit (LIKE, exact match, heuristic on account code prefix, etc.).
 */
enum SearchMode: string
{
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case Exact = 'exact';
    case AccountPrefix = 'account_prefix';
}
