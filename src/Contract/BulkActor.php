<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Contract;

/**
 * Marker interface for the actor that triggers a bulk action.
 *
 * The bulk-action subsystem ({@see \Quorae\GridBundle\Dto\BulkActionRequest},
 * {@see \Quorae\GridBundle\Handler\BulkActionExecutor},
 * {@see \Quorae\GridBundle\Twig\Components\LiveGrid}) needs to carry the
 * currently authenticated principal through to the
 * {@see BulkActionHandler}. A standalone bundle cannot reference a host
 * user entity, so the host's user class implements this empty marker and
 * the framework stays decoupled from any concrete security model.
 *
 * Empty by design : the framework never reads anything off the actor — it
 * only forwards the instance untouched to the handler, which knows its own
 * user type.
 */
interface BulkActor
{
}
