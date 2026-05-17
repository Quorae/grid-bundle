<?php

declare(strict_types=1);

namespace Quorae\GridBundle;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;
use Quorae\GridBundle\Contract\ChoicesProviderInterface;
use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\DependencyInjection\QuoraeGridExtension;
use Quorae\GridBundle\Registry\GridRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class QuoraeGridBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Moved here from AICD Kernel::build() (port-map §1.3/§4.2): the bundle
        // is self-contained — no host kernel edit. Auto-tag every `#[AsGrid]`
        // class so the compiler pass discovers them without manual wiring.
        $container->registerAttributeForAutoconfiguration(
            AsGrid::class,
            GridRegistryCompilerPass::attributeTagger(),
        );

        // AICD `services.yaml` _instanceof block, applied bundle-wide so host
        // App services (repositories implementing the contracts, grid choices
        // providers, bulk handlers) are auto-tagged without a host kernel edit
        // (port-map §1.4 / §4.2). `_instanceof` inside the bundle's loaded
        // services.php only covers bundle-internal services — host services
        // defined in the app's own services.yaml need this global
        // registerForAutoconfiguration hook.
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

    public function getContainerExtension(): ExtensionInterface
    {
        return new QuoraeGridExtension();
    }
}
