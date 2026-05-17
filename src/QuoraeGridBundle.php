<?php

declare(strict_types=1);

namespace Quorae\GridBundle;

use Quorae\GridBundle\Attribute\AsGrid;
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

        $container->addCompilerPass(new GridRegistryCompilerPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new QuoraeGridExtension();
    }
}
