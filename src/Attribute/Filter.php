<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

use Quorae\GridBundle\Contract\ChoicesProviderInterface;
use Quorae\GridBundle\Enum\FilterType;

/**
 * Declares a filter exposed in the grid's filter bar.
 *
 * `choicesProvider` — fully-qualified class-string of a service implementing
 * {@see ChoicesProviderInterface}. Resolved lazily at render-time via a
 * service locator so the compile-time grid definition stays pure data.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final readonly class Filter
{
    /**
     * @param array<int|string, scalar>                   $choices
     * @param class-string<ChoicesProviderInterface>|null $choicesProvider
     */
    public function __construct(
        public FilterType $type,
        public ?string $label = null,
        public array $choices = [],
        public ?string $choicesProvider = null,
        public ?string $caption = null,
        public bool $valueMonospace = false,
        public ?string $group = null,
    ) {
    }
}
