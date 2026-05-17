<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Exception;

/**
 * Raised when the data-source service referenced by a grid cannot be
 * resolved from the service container — typically because the class does
 * not declare `implements GridDataSource` or is not registered as a
 * service.
 */
final class MissingDataSourceException extends \LogicException implements GridExceptionInterface
{
    public static function forGrid(string $gridName, string $dataSourceClass): self
    {
        return new self(\sprintf(
            'Grid "%s" declares data source "%s" but no such service is registered. '
            .'Ensure the class implements Quorae\\GridBundle\\Contract\\GridDataSource and is registered as a service.',
            $gridName,
            $dataSourceClass,
        ));
    }
}
