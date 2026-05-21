<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Definition;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\BulkAction;
use Quorae\GridBundle\Attribute\RowId;
use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;

/**
 * Reads `#[BulkAction]` and `#[RowId]` attributes from a grid class and
 * produces a list of {@see BulkActionDefinition} objects.
 *
 * Extracted from {@see GridDefinitionResolver} so the resolver stays
 * focused on orchestrating the full grid definition assembly while
 * bulk-action concerns live here.
 *
 * Instantiated via `new` at compile-time — no DI, no state.
 */
final class BulkActionReader
{
    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<BulkActionDefinition>
     */
    public function readBulkActions(\ReflectionClass $reflection, AsGrid $asGrid): array
    {
        $gridClass = $reflection->getName();
        $attributes = $reflection->getAttributes(BulkAction::class);
        if ($attributes === []) {
            return [];
        }

        $definitions = [];
        $seenNames = [];
        foreach ($attributes as $attribute) {
            /** @var BulkAction $bulkAction */
            $bulkAction = $attribute->newInstance();

            if (!$asGrid->interactive) {
                throw InvalidGridDefinitionException::bulkActionRequiresInteractive($gridClass, $bulkAction->name);
            }

            if (isset($seenNames[$bulkAction->name])) {
                throw InvalidGridDefinitionException::duplicateBulkActionName($gridClass, $bulkAction->name);
            }
            $seenNames[$bulkAction->name] = true;

            $this->assertHandlerRouteExclusivity($gridClass, $bulkAction);

            if ($bulkAction->handler !== null) {
                $this->assertHandlerImplementsInterface($gridClass, $bulkAction->handler);
                \assert($bulkAction->ownershipValidator !== null);
                $this->assertValidatorImplementsInterface($gridClass, $bulkAction->ownershipValidator);
            }

            $definitions[] = new BulkActionDefinition(
                name: $bulkAction->name,
                label: $bulkAction->label,
                handlerService: $bulkAction->handler,
                ownershipValidator: $bulkAction->ownershipValidator,
                destructive: $bulkAction->destructive,
                icon: $bulkAction->icon,
                confirmMessage: $bulkAction->confirmMessage,
                requiredRole: $bulkAction->requiredRole,
                route: $bulkAction->route,
            );
        }

        return $definitions;
    }

    /**
     * @param list<BulkActionDefinition> $bulkActions
     * @param class-string               $gridClass
     */
    public function resolveRowIdProperty(AsGrid $asGrid, array $bulkActions, string $gridClass): ?string
    {
        if ($bulkActions === []) {
            return null;
        }

        $rowClass = $asGrid->rowClass;
        if ($rowClass === null || !class_exists($rowClass)) {
            throw InvalidGridDefinitionException::bulkActionRequiresRowId($gridClass, $rowClass ?? '(not declared)');
        }

        $rowReflection = new \ReflectionClass($rowClass);
        $attributed = $this->findAttributedRowIdProperty($rowReflection, $gridClass);
        if ($attributed !== null) {
            return $attributed;
        }

        $conventional = $this->findConventionalIdProperty($rowReflection);
        if ($conventional !== null) {
            return $conventional;
        }

        throw InvalidGridDefinitionException::bulkActionRequiresRowId($gridClass, $rowClass);
    }

    /**
     * @param class-string $gridClass
     */
    private function assertHandlerRouteExclusivity(string $gridClass, BulkAction $bulkAction): void
    {
        $hasHandler = $bulkAction->handler !== null;
        $hasRoute = $bulkAction->route !== null;

        if ($hasHandler && $hasRoute) {
            throw InvalidGridDefinitionException::bulkActionHandlerAndRouteMutuallyExclusive($gridClass, $bulkAction->name);
        }

        if (!$hasHandler && !$hasRoute) {
            throw InvalidGridDefinitionException::bulkActionMissingHandlerOrRoute($gridClass, $bulkAction->name);
        }

        if ($hasHandler && $bulkAction->ownershipValidator === null) {
            throw InvalidGridDefinitionException::bulkActionHandlerRequiresValidator($gridClass, $bulkAction->name);
        }
    }

    /**
     * @param class-string $gridClass
     */
    private function assertHandlerImplementsInterface(string $gridClass, string $handlerFqcn): void
    {
        if (!class_exists($handlerFqcn)) {
            throw InvalidGridDefinitionException::bulkActionHandlerMissingInterface($gridClass, $handlerFqcn);
        }
        $handlerReflection = new \ReflectionClass($handlerFqcn);
        if (!$handlerReflection->implementsInterface(BulkActionHandler::class)) {
            throw InvalidGridDefinitionException::bulkActionHandlerMissingInterface($gridClass, $handlerFqcn);
        }
    }

    /**
     * @param class-string $gridClass
     */
    private function assertValidatorImplementsInterface(string $gridClass, string $validatorFqcn): void
    {
        if (!class_exists($validatorFqcn)) {
            throw InvalidGridDefinitionException::ownershipValidatorMissingInterface($gridClass, $validatorFqcn);
        }
        $validatorReflection = new \ReflectionClass($validatorFqcn);
        if (!$validatorReflection->implementsInterface(BulkOwnershipValidator::class)) {
            throw InvalidGridDefinitionException::ownershipValidatorMissingInterface($gridClass, $validatorFqcn);
        }
    }

    /**
     * @param \ReflectionClass<object> $rowReflection
     * @param class-string             $gridClass
     */
    private function findAttributedRowIdProperty(\ReflectionClass $rowReflection, string $gridClass): ?string
    {
        $matches = [];
        foreach ($rowReflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getAttributes(RowId::class) === []) {
                continue;
            }
            $matches[] = $property->getName();
        }

        if (\count($matches) > 1) {
            throw InvalidGridDefinitionException::duplicateRowIdAttribute($gridClass, $rowReflection->getName());
        }

        return $matches[0] ?? null;
    }

    /**
     * @param \ReflectionClass<object> $rowReflection
     */
    private function findConventionalIdProperty(\ReflectionClass $rowReflection): ?string
    {
        if (!$rowReflection->hasProperty('id')) {
            return null;
        }
        $property = $rowReflection->getProperty('id');
        if (!$property->isPublic()) {
            return null;
        }
        $type = $property->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }
        if (!\in_array($type->getName(), ['int', 'string'], true)) {
            return null;
        }

        return 'id';
    }
}
