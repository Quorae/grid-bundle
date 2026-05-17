<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Registry;

use Quorae\GridBundle\Contract\BulkOwnershipValidator;
use Quorae\GridBundle\Exception\BulkActionException;
use Psr\Container\ContainerInterface;

/**
 * Service locator for {@see BulkOwnershipValidator} implementations.
 *
 * Same pattern as {@see BulkActionHandlerRegistry}: populated at
 * compile-time via `!tagged_locator`, indexed by FQCN.
 */
final readonly class OwnershipValidatorRegistry
{
    public function __construct(
        private ContainerInterface $validators,
    ) {
    }

    /**
     * @throws BulkActionException when no validator is registered under the given FQCN
     */
    public function get(string $validatorFqcn): BulkOwnershipValidator
    {
        if (!$this->validators->has($validatorFqcn)) {
            throw BulkActionException::validatorNotTagged($validatorFqcn);
        }

        /** @var BulkOwnershipValidator $validator */
        $validator = $this->validators->get($validatorFqcn);

        return $validator;
    }
}
