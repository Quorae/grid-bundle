<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Registry;

use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Exception\BulkActionException;
use Psr\Container\ContainerInterface;

/**
 * Service locator for every {@see BulkActionHandler} in the application.
 *
 * Populated at compile-time by {@see GridRegistryCompilerPass} through a
 * tagged-iterator service — each handler is indexed by its FQCN so the
 * LiveGrid can resolve a handler from a {@see \Quorae\GridBundle\Definition\BulkActionDefinition::$handlerService}
 * class-string with a single `get()` call.
 *
 * `final readonly` — stateless and safe to share across concurrent LiveGrid
 * invocations.
 */
final readonly class BulkActionHandlerRegistry
{
    public function __construct(
        private ContainerInterface $handlers,
    ) {
    }

    /**
     * @throws BulkActionException when no handler service is registered under the given FQCN
     */
    public function get(string $handlerFqcn): BulkActionHandler
    {
        if (!$this->handlers->has($handlerFqcn)) {
            throw BulkActionException::handlerNotTagged($handlerFqcn);
        }

        /** @var BulkActionHandler $handler */
        $handler = $this->handlers->get($handlerFqcn);

        return $handler;
    }
}
