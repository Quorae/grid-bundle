<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Contract\ChoicesProviderInterface;
use Quorae\GridBundle\Enum\FilterType;

/**
 * Compile-time snapshot of a `#[Filter]` attribute. The `choicesProvider`
 * reference is left as a class-string — actual instantiation happens at
 * render time via a service locator wired in the handler.
 */
final readonly class FilterDefinition
{
    /**
     * @param array<int|string, scalar>                   $choices
     * @param class-string<ChoicesProviderInterface>|null $choicesProvider
     */
    public function __construct(
        public string $propertyName,
        public FilterType $type,
        public string $label,
        public array $choices,
        public ?string $choicesProvider,
        public ?string $caption = null,
        public bool $valueMonospace = false,
        public ?string $group = null,
    ) {
    }

    /**
     * @param array<int|string, scalar> $choices
     */
    public function withChoices(array $choices): self
    {
        return new self(
            propertyName: $this->propertyName,
            type: $this->type,
            label: $this->label,
            choices: $choices,
            choicesProvider: $this->choicesProvider,
            caption: $this->caption,
            valueMonospace: $this->valueMonospace,
            group: $this->group,
        );
    }
}
