<?php

declare(strict_types=1);

namespace Quorae\GridBundle;

use Quorae\GridBundle\DependencyInjection\QuoraeGridExtension;
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

        // WP-2: register GridRegistryCompilerPass + #[AsGrid] attribute autoconfig here
        // (see port-map §1.3 — deferred until Registry\GridRegistryCompilerPass exists so
        //  the kernel boots cleanly with zero grid classes in WP-1).
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new QuoraeGridExtension();
    }
}
