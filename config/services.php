<?php

declare(strict_types=1);

use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;
use Quorae\GridBundle\Contract\ChoicesProviderInterface;
use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Definition\GridDefinitionResolver;
use Quorae\GridBundle\Handler\BulkActionExecutor;
use Quorae\GridBundle\Handler\FilterHydrator;
use Quorae\GridBundle\Handler\PageParser;
use Quorae\GridBundle\Handler\RenderGridHandler;
use Quorae\GridBundle\Handler\ScalarCoercer;
use Quorae\GridBundle\Registry\BulkActionHandlerRegistry;
use Quorae\GridBundle\Registry\GridRegistry;
use Quorae\GridBundle\Registry\OwnershipValidatorRegistry;
use Quorae\GridBundle\Twig\Components\Grid;
use Quorae\GridBundle\Twig\Components\LiveGrid;
use Quorae\GridBundle\Twig\GridExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/*
 * Bundle DI wiring (PHP-DSL) — port-map §1.4, reproduces AICD services.yaml
 * Grid block (lines 23-74) as bundle-scoped definitions. Tag names renamed
 * `app.grid.*` → `quorae_grid.*` (bundle hygiene; values are bundle-internal).
 */
return static function (ContainerConfigurator $c): void {
    $services = $c->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // _instanceof tags (ported verbatim from AICD services.yaml _instanceof block).
    $services->instanceof(GridDataSource::class)->tag('quorae_grid.data_source');
    $services->instanceof(ChoicesProviderInterface::class)->tag('quorae_grid.choices_provider');
    $services->instanceof(BulkActionHandler::class)->tag('quorae_grid.bulk_action_handler');
    $services->instanceof(BulkOwnershipValidator::class)->tag('quorae_grid.ownership_validator');

    $services->set(GridDefinitionResolver::class);
    $services->set(ScalarCoercer::class);
    $services->set(FilterHydrator::class);
    $services->set(PageParser::class);

    // GridRegistry hand-registered so GridRegistryCompilerPass has a definition
    // to mutate. Argument #0 (name → class-string map) is populated by the pass.
    $services->set(GridRegistry::class)
        ->args([[], service(GridDefinitionResolver::class)]);

    $services->set(RenderGridHandler::class)
        ->args([
            service(GridRegistry::class),
            tagged_locator('quorae_grid.data_source', indexAttribute: 'class'),
            tagged_locator('quorae_grid.choices_provider', indexAttribute: 'class'),
            service(FilterHydrator::class),
            service(PageParser::class),
        ]);

    $services->set(BulkActionHandlerRegistry::class)
        ->args([tagged_locator('quorae_grid.bulk_action_handler', indexAttribute: 'class')]);
    $services->set(OwnershipValidatorRegistry::class)
        ->args([tagged_locator('quorae_grid.ownership_validator', indexAttribute: 'class')]);

    // Security autowired — only resolved if a bulk action actually fires.
    $services->set(BulkActionExecutor::class);

    // Twig extension — grid_row_classes (RowSignature). GridFormattingExtension
    // (montant_fr / enum_value) lands in WP-3 alongside the templates.
    $services->set(GridExtension::class)->tag('twig.extension');

    // Twig components (UX) — twig.component tags added by autoconfigure when
    // ux-twig-component / ux-live-component are present in the host.
    $services->set(Grid::class);
    $services->set(LiveGrid::class);
};
