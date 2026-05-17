<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

/**
 * Ledger (direction *Ledger*) visual signatures applied to rows or cells.
 *
 * `TotalRule` — 1px CSS rule above a total line.
 * `AnomalyBar` — 2px left bar marking a row with a detected anomaly.
 * `Subtotal` — 1px dotted rule for a by-class subtotal row.
 *
 * These are **identifiers** : the actual CSS lives in
 * `assets/styles/components/grid.css` and uses the tokens declared via
 * `@theme` in `assets/styles/app.css`. Neither the backend nor the Twig
 * component is allowed to hard-code a color here.
 */
enum LedgerSignature: string
{
    case TotalRule = 'total_rule';
    case AnomalyBar = 'anomaly_bar';
    case Subtotal = 'subtotal';
}
