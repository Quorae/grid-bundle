<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

use Quorae\GridBundle\Enum\Formatter;

/**
 * Declares a column exposed by the grid. Attached to a public property of a
 * grid class (the property name is not a live field — it is a declarative
 * slot used to read the column metadata at compile-time).
 *
 * `sortable` carries the SQL column name fed to `ORDER BY` — the repository
 * remains souverain on its SQL, the framework only forwards the hint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Column
{
    public function __construct(
        public string $label,
        public ?string $class = null,
        public Formatter $formatter = Formatter::Plain,
        public ?string $template = null,
        public string|false $sortable = false,
        public bool $hideOnMobile = false,
        /**
         * Explicit column width expressed as a CSS value (e.g. '220px', '14rem').
         * When set, the `<th>` element receives a `style="width: ..."` attribute.
         * Under `table-layout: fixed` this value is honoured as the column width,
         * overriding the default percentage widths defined in `grid.css`.
         * Use for columns whose content has a known minimum render size
         * (e.g. action columns that render a split-button with a fixed-width chevron).
         */
        public ?string $colWidth = null,
    ) {
    }
}
