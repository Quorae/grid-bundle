<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Registry;

use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Definition\GridDefinitionResolver;
use Quorae\GridBundle\Exception\GridNotFoundException;

/**
 * Compile-time map of every `#[AsGrid]` class in the codebase, keyed by
 * grid name.
 *
 * The compiler pass injects the **class-string map** (plain `array<string,
 * string>` — dumpable by Symfony's container compiler) plus a
 * {@see GridDefinitionResolver}. `GridDefinition` instances are rebuilt
 * lazily on the first `get()` call and memoised for the request lifetime —
 * constant-time lookup, zero overhead once warm.
 *
 * Uniqueness of grid names is still enforced at compile-time inside
 * {@see GridRegistryCompilerPass} ; this class is a thin, stateful reader.
 *
 * Not `final` — unit tests for downstream consumers (e.g. the Live Component)
 * subclass it to provide pre-built definitions without round-tripping through
 * attribute reflection.
 */
class GridRegistry
{
    /** @var array<string, GridDefinition> */
    private array $resolved = [];

    /**
     * @param array<string, class-string> $gridClassesByName
     */
    public function __construct(
        private readonly array $gridClassesByName,
        private readonly GridDefinitionResolver $resolver,
    ) {
    }

    public function get(string $name): GridDefinition
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }
        if (!isset($this->gridClassesByName[$name])) {
            throw GridNotFoundException::byName($name);
        }

        return $this->resolved[$name] = $this->resolver->resolve($this->gridClassesByName[$name]);
    }
}
