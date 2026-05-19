<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

use Quorae\GridBundle\Definition\GridDefinition;
use Symfony\Component\HttpFoundation\Request;

/**
 * Hydrates the filter DTO declared on a grid definition from the HTTP
 * request and runtime context.
 *
 * Responsibilities:
 *  - Merge raw values from `?q=`, `?criteria[<key>]=<val>`, and `$extraContext`
 *  - Instantiate the filter DTO through reflection on its constructor
 *  - Coerce scalar string values from query strings into typed constructor parameters
 *  - Fall back to safe defaults when user-supplied values are invalid
 *
 * Extracted from {@see RenderGridHandler} to respect SRP — the handler
 * orchestrates the grid rendering pipeline, this service handles DTO hydration.
 */
final readonly class FilterHydrator
{
    public function __construct(
        private ScalarCoercer $coercer,
    ) {
    }

    /**
     * @param array<string, mixed> $extraContext overrides for filter properties
     *                                           that are never exposed in the
     *                                           query string (e.g. `clientId`)
     */
    public function hydrate(GridDefinition $definition, Request $request, array $extraContext = []): object
    {
        $filterClass = $definition->filterClass;

        $raw = $this->collectRawValues($request, $extraContext);

        if ($filterClass === \stdClass::class) {
            $filter = new \stdClass();
            foreach ($raw as $key => $value) {
                $filter->{$key} = $value;
            }

            return $filter;
        }

        $filterReflection = new \ReflectionClass($filterClass);
        $constructor = $filterReflection->getConstructor();
        if ($constructor === null) {
            return $filterReflection->newInstance();
        }

        $declaredFilterProperties = $this->buildDeclaredPropertyIndex($definition);
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $arguments[$name] = $this->resolveConstructorArgument(
                parameter: $parameter,
                raw: $raw,
                declaredInGrid: isset($declaredFilterProperties[$name]),
            );
        }

        try {
            return $filterReflection->newInstanceArgs($arguments);
        } catch (\InvalidArgumentException|\ValueError|\TypeError) {
            return $this->instantiateWithSafeDefaults(
                constructor: $constructor,
                filterReflection: $filterReflection,
                extraContext: $extraContext,
                computedArguments: $arguments,
            );
        }
    }

    /**
     * @param \ReflectionClass<object> $filterReflection
     * @param array<string, mixed>     $extraContext
     * @param array<string, mixed>     $computedArguments pre-computed values from the first hydration pass
     */
    private function instantiateWithSafeDefaults(
        \ReflectionMethod $constructor,
        \ReflectionClass $filterReflection,
        array $extraContext,
        array $computedArguments = [],
    ): object {
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (\array_key_exists($name, $extraContext)) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $coerced = $this->coercer->coerce($extraContext[$name], $type);
                    $arguments[$name] = $coerced === null && !$type->allowsNull()
                        ? $this->defaultFor($parameter)
                        : $coerced;
                    continue;
                }
                $arguments[$name] = $extraContext[$name];
                continue;
            }
            if (\array_key_exists($name, $computedArguments)) {
                $arguments[$name] = $computedArguments[$name];
                continue;
            }
            $arguments[$name] = $this->defaultFor($parameter);
        }

        try {
            return $filterReflection->newInstanceArgs($arguments);
        } catch (\InvalidArgumentException|\ValueError|\TypeError) {
            $fallback = [];
            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (\array_key_exists($name, $extraContext)) {
                    $fallback[$name] = $arguments[$name];
                    continue;
                }
                $fallback[$name] = $this->defaultFor($parameter);
            }

            return $filterReflection->newInstanceArgs($fallback);
        }
    }

    /**
     * @return array<string, true>
     */
    private function buildDeclaredPropertyIndex(GridDefinition $definition): array
    {
        $index = [];
        foreach ($definition->filters as $filter) {
            $index[$filter->propertyName] = true;
        }
        if ($definition->search !== null) {
            $index[$definition->search->propertyName] = true;
        }

        return $index;
    }

    /**
     * @param array<string, mixed> $extraContext
     *
     * @return array<string, mixed>
     */
    private function collectRawValues(Request $request, array $extraContext): array
    {
        $raw = [];

        $query = $request->query;
        $qValue = $query->get('q');
        if (\is_string($qValue)) {
            $raw['q'] = mb_substr($qValue, 0, 255);
        }

        $criteria = $query->all('criteria');
        foreach ($criteria as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }
            $raw[$key] = $value;
        }

        foreach ($extraContext as $key => $value) {
            $raw[$key] = $value;
        }

        return $raw;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function resolveConstructorArgument(\ReflectionParameter $parameter, array $raw, bool $declaredInGrid): mixed
    {
        $name = $parameter->getName();

        $rawKey = $this->matchRawKey($name, $raw);
        if ($rawKey === null) {
            return $this->defaultFor($parameter);
        }

        $rawValue = $raw[$rawKey];
        $type = $parameter->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return $rawValue;
        }

        $coerced = $this->coercer->coerce($rawValue, $type);

        if ($coerced === '' && $type->getName() === 'string' && $type->allowsNull() && $name !== 'q') {
            return null;
        }

        if ($coerced === null && !$type->allowsNull()) {
            return $this->defaultFor($parameter);
        }

        return $coerced;
    }

    /**
     * Resolves which raw key feeds a constructor parameter.
     *
     * The rendered filter UI emits snake_case query-string keys
     * (`criteria[date_from]`) while filter DTO constructors declare
     * camelCase parameters (`dateFrom`) — see the §7-frozen public contract.
     * Exact match wins so any already-working camelCase key never regresses;
     * the snake_case form of the parameter name is the documented fallback.
     *
     * @param array<string, mixed> $raw
     */
    private function matchRawKey(string $parameterName, array $raw): ?string
    {
        if (\array_key_exists($parameterName, $raw)) {
            return $parameterName;
        }

        $snakeName = $this->camelToSnake($parameterName);
        if ($snakeName !== $parameterName && \array_key_exists($snakeName, $raw)) {
            return $snakeName;
        }

        $camelName = $this->snakeToCamel($parameterName);
        if ($camelName !== $parameterName && \array_key_exists($camelName, $raw)) {
            return $camelName;
        }

        return null;
    }

    private function camelToSnake(string $value): string
    {
        $snake = (string) preg_replace('/[A-Z]/', '_$0', $value);

        return strtolower($snake);
    }

    private function snakeToCamel(string $value): string
    {
        if (!str_contains($value, '_')) {
            return $value;
        }

        return lcfirst(str_replace('_', '', ucwords($value, '_')));
    }

    private function defaultFor(\ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        if ($parameter->allowsNull()) {
            return null;
        }

        throw new \InvalidArgumentException(\sprintf('Cannot hydrate required parameter "$%s" — no value in request and no default.', $parameter->getName()));
    }
}
