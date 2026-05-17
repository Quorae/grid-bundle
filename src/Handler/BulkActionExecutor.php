<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

use Quorae\GridBundle\Contract\BulkActor;
use Quorae\GridBundle\Definition\BulkActionDefinition;
use Quorae\GridBundle\Dto\BulkActionRequest;
use Quorae\GridBundle\Dto\BulkActionResult;
use Quorae\GridBundle\Exception\BulkActionException;
use Quorae\GridBundle\Registry\BulkActionHandlerRegistry;
use Quorae\GridBundle\Registry\GridRegistry;
use Quorae\GridBundle\Registry\OwnershipValidatorRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Executes a bulk action on a set of selected row ids.
 *
 * Pipeline : resolve definition → authorise role → validate selection →
 * **validate ownership** → invoke handler. Every guard throws
 * {@see BulkActionException} so the caller (typically the LiveGrid component)
 * can translate failures into flash messages at the UI layer.
 *
 * Extracted from {@see \Quorae\GridBundle\Twig\Components\LiveGrid} so the Live
 * Component stays thin (template-oriented state management) while the
 * business pipeline lives here.
 */
final readonly class BulkActionExecutor
{
    /**
     * Hard cap — defence in depth. Each handler may impose its own limit.
     */
    public const int MAX_BULK_SELECTION = 1000;

    public function __construct(
        private GridRegistry $grids,
        private BulkActionHandlerRegistry $handlers,
        private OwnershipValidatorRegistry $ownershipValidators,
        private Security $security,
    ) {
    }

    /**
     * @param list<int|string>     $selectedIds
     * @param array<string, mixed> $extraContext
     */
    public function execute(
        string $gridName,
        string $actionName,
        array $selectedIds,
        array $extraContext,
        BulkActor $actor,
    ): BulkActionResult {
        $bulkAction = $this->resolveAndAuthorise($gridName, $actionName);
        $this->validateSelection($selectedIds);

        $ownedIds = $this->filterByOwnership($bulkAction, $selectedIds, $extraContext);

        $handler = $this->handlers->get($bulkAction->handlerService);

        return $handler(new BulkActionRequest(
            gridName: $gridName,
            actionName: $actionName,
            rowIds: $ownedIds,
            extraContext: $extraContext,
            actor: $actor,
        ));
    }

    /**
     * @param list<int|string>     $selectedIds
     * @param array<string, mixed> $extraContext
     *
     * @return list<int|string>
     */
    private function filterByOwnership(BulkActionDefinition $bulkAction, array $selectedIds, array $extraContext): array
    {
        $validator = $this->ownershipValidators->get($bulkAction->ownershipValidator);
        $ownedIds = $validator->filterOwned($selectedIds, $extraContext);

        if ($ownedIds === []) {
            throw BulkActionException::ownershipRejected(\count($selectedIds));
        }

        return $ownedIds;
    }

    private function resolveAndAuthorise(string $gridName, string $actionName): BulkActionDefinition
    {
        $definition = $this->grids->get($gridName);
        foreach ($definition->bulkActions as $bulk) {
            if ($bulk->name === $actionName) {
                if (!$this->security->isGranted($bulk->requiredRole)) {
                    throw BulkActionException::accessDenied($actionName, $bulk->requiredRole);
                }

                return $bulk;
            }
        }

        throw BulkActionException::unknownAction($gridName, $actionName);
    }

    /**
     * @param list<int|string> $selectedIds
     */
    private function validateSelection(array $selectedIds): void
    {
        if ($selectedIds === []) {
            throw BulkActionException::emptySelection();
        }

        if (\count($selectedIds) > self::MAX_BULK_SELECTION) {
            throw BulkActionException::selectionTooLarge(\count($selectedIds), self::MAX_BULK_SELECTION);
        }
    }
}
