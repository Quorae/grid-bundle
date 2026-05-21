<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Exception;

use Quorae\GridBundle\Enum\BulkActionErrorKind;

/**
 * Runtime failure raised by the bulk-action pipeline — distinct from
 * {@see InvalidGridDefinitionException} which fires at compile-time.
 *
 * All factories produce fully-formed messages so callers stay readable.
 */
final class BulkActionException extends \RuntimeException implements GridExceptionInterface
{
    public readonly BulkActionErrorKind $kind;

    private function __construct(BulkActionErrorKind $kind, string $message)
    {
        parent::__construct($message);
        $this->kind = $kind;
    }

    public static function unknownAction(string $gridName, string $actionName): self
    {
        return new self(BulkActionErrorKind::UnknownAction, 'Action non reconnue.');
    }

    public static function handlerNotTagged(string $handlerFqcn): self
    {
        return new self(BulkActionErrorKind::HandlerNotTagged, \sprintf(
            'Bulk action handler "%s" is not registered in the service locator — make sure the class implements BulkActionHandler and is tagged "quorae_grid.bulk_action_handler".',
            $handlerFqcn,
        ));
    }

    public static function accessDenied(string $actionName, string $requiredRole): self
    {
        return new self(BulkActionErrorKind::AccessDenied, 'Vous n\'avez pas les droits pour cette action.');
    }

    public static function emptySelection(): self
    {
        return new self(BulkActionErrorKind::EmptySelection, 'Aucune ligne sélectionnée.');
    }

    public static function selectionTooLarge(int $count, int $max): self
    {
        return new self(BulkActionErrorKind::SelectionTooLarge, \sprintf(
            'Sélection trop large (%d lignes) — limite de %d.',
            $count,
            $max,
        ));
    }

    public static function validatorNotTagged(string $validatorFqcn): self
    {
        return new self(BulkActionErrorKind::ValidatorNotTagged, \sprintf(
            'Ownership validator "%s" is not registered in the service locator — make sure the class implements BulkOwnershipValidator and is tagged "quorae_grid.ownership_validator".',
            $validatorFqcn,
        ));
    }

    public static function ownershipRejected(int $submittedCount): self
    {
        return new self(BulkActionErrorKind::OwnershipRejected, \sprintf(
            'Aucune des %d ligne(s) sélectionnée(s) n\'appartient au périmètre courant.',
            $submittedCount,
        ));
    }

    public static function routeBasedAction(string $actionName): self
    {
        return new self(BulkActionErrorKind::RouteBasedAction, \sprintf(
            'Bulk action "%s" is route-based and cannot be executed through the handler pipeline.',
            $actionName,
        ));
    }
}
