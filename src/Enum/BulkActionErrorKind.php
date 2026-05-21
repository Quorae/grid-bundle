<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Enum;

enum BulkActionErrorKind: string
{
    case EmptySelection = 'empty_selection';
    case SelectionTooLarge = 'selection_too_large';
    case UnknownAction = 'unknown_action';
    case HandlerNotTagged = 'handler_not_tagged';
    case ValidatorNotTagged = 'validator_not_tagged';
    case OwnershipRejected = 'ownership_rejected';
    case AccessDenied = 'access_denied';
    case RouteBasedAction = 'route_based_action';
}
