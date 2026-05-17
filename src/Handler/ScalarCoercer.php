<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

/**
 * Stateless scalar coercion — transforms loose HTTP string values into
 * typed PHP scalars or backed-enum instances.
 *
 * Extracted from {@see FilterHydrator} so the hydrator focuses on DTO
 * assembly while this service owns the type-mapping rules. Every method
 * is a pure function : no state, no side-effects.
 */
final readonly class ScalarCoercer
{
    /**
     * Dispatches to the appropriate coercion method based on the reflected
     * type of a constructor parameter.
     */
    public function coerce(mixed $value, \ReflectionNamedType $type): mixed
    {
        if ($type->isBuiltin()) {
            return match ($type->getName()) {
                'int' => $this->coerceInt($value),
                'string' => $this->coerceString($value),
                'bool' => $this->coerceBool($value),
                'float' => $this->coerceFloat($value),
                default => null,
            };
        }

        $className = $type->getName();
        if (is_subclass_of($className, \BackedEnum::class)) {
            return $this->coerceEnum($value, $className);
        }

        if ($value instanceof $className) {
            return $value;
        }

        return null;
    }

    public function coerceInt(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (\is_string($value) && $value !== '' && filter_var($value, \FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        return null;
    }

    public function coerceString(mixed $value): ?string
    {
        if (\is_string($value)) {
            return $value;
        }
        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    public function coerceBool(mixed $value): ?bool
    {
        if (\is_bool($value)) {
            return $value;
        }
        if (\is_string($value)) {
            $result = filter_var($value, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);

            return \is_bool($result) ? $result : null;
        }
        if (\is_int($value)) {
            return $value !== 0;
        }

        return null;
    }

    public function coerceFloat(mixed $value): ?float
    {
        if (\is_float($value)) {
            return $value;
        }
        if (\is_int($value)) {
            return (float) $value;
        }
        if (\is_string($value) && $value !== '' && filter_var($value, \FILTER_VALIDATE_FLOAT) !== false) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    public function coerceEnum(mixed $value, string $enumClass): ?\BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }
        if (\is_string($value) || \is_int($value)) {
            return $enumClass::tryFrom($value);
        }

        return null;
    }
}
