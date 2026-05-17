<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// WP-1: empty-but-valid. The full Grid DI block (port-map §1.4: _instanceof tags,
// GridDefinitionResolver, RenderGridHandler, registries, Twig extensions/components)
// lands in WP-2 once the referenced Quorae\GridBundle\* classes exist.
return static function (ContainerConfigurator $c): void {};
