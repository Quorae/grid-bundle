<?php

declare(strict_types=1);

namespace Quorae\GridBundle;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;
use Quorae\GridBundle\Contract\ChoicesProviderInterface;
use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Registry\GridRegistryCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class QuoraeGridBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /** @param array<array-key, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $loader = new PhpFileLoader(
            $builder,
            new FileLocator(\dirname(__DIR__) . '/config'),
        );
        $loader->load('services.php');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(
            AsGrid::class,
            GridRegistryCompilerPass::attributeTagger(),
        );

        $container->registerForAutoconfiguration(GridDataSource::class)
            ->addTag('quorae_grid.data_source');
        $container->registerForAutoconfiguration(ChoicesProviderInterface::class)
            ->addTag('quorae_grid.choices_provider');
        $container->registerForAutoconfiguration(BulkActionHandler::class)
            ->addTag('quorae_grid.bulk_action_handler');
        $container->registerForAutoconfiguration(BulkOwnershipValidator::class)
            ->addTag('quorae_grid.ownership_validator');

        $container->addCompilerPass(new GridRegistryCompilerPass());
    }
}
