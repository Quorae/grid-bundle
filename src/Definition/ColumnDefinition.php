<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Enum\Formatter;

/**
 * Compile-time snapshot of a `#[Column]` — immutable by design, the
 * resolver builds it once and the registry hands the same instance to
 * every request that renders the grid.
 */
final readonly class ColumnDefinition
{
    public function __construct(
        public string $propertyName,
        public string $label,
        public ?string $class,
        public Formatter $formatter,
        public ?string $template,
        public string|false $sortable,
        public bool $hideOnMobile,
        public ?string $colWidth = null,
    ) {
    }
}
