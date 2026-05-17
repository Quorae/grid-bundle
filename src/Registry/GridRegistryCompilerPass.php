<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Registry;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Walks every service tagged `quorae_grid` (tag applied by the
 * `AttributeAutoconfiguration` wire registered in the bundle), reads
 * each class's `#[AsGrid]` attribute for the grid name, enforces name
 * uniqueness and injects the resulting map into the {@see GridRegistry}
 * constructor.
 *
 * Full definition resolution (columns, filters, search, row-signatures)
 * is deferred to {@see GridRegistry::get()} at runtime — one reflection
 * pass per grid instead of two.
 */
final class GridRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * Tag applied by the attribute autoconfiguration in
     * {@see \Quorae\GridBundle\QuoraeGridBundle::build()}.
     */
    public const string TAG = 'quorae_grid';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(GridRegistry::class)) {
            return;
        }

        /** @var array<string, class-string> $gridClassesByName */
        $gridClassesByName = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $serviceId => $tags) {
            /** @var class-string $gridClass */
            $gridClass = $serviceId;

            $name = $this->readGridName($gridClass);

            if (isset($gridClassesByName[$name])) {
                throw InvalidGridDefinitionException::duplicateName($name, $gridClassesByName[$name], $gridClass);
            }

            $gridClassesByName[$name] = $gridClass;
        }

        ksort($gridClassesByName);

        $registryDefinition = $container->findDefinition(GridRegistry::class);
        $registryDefinition->setArgument(0, $gridClassesByName);
    }

    /**
     * @param class-string $gridClass
     */
    private function readGridName(string $gridClass): string
    {
        $reflection = new \ReflectionClass($gridClass);
        $attributes = $reflection->getAttributes(AsGrid::class);
        if ($attributes === []) {
            throw InvalidGridDefinitionException::missingAsGrid($gridClass);
        }

        /** @var AsGrid $asGrid */
        $asGrid = $attributes[0]->newInstance();

        return $asGrid->name;
    }

    /**
     * Factory used by {@see \Quorae\GridBundle\QuoraeGridBundle::build()} —
     * binds `#[AsGrid]` to our {@see self::TAG} via
     * `ContainerBuilder::registerAttributeForAutoconfiguration()`.
     */
    public static function attributeTagger(): \Closure
    {
        $tag = self::TAG;

        return static function (\Symfony\Component\DependencyInjection\ChildDefinition $definition, AsGrid $attribute) use ($tag): void {
            $definition->addTag($tag);
        };
    }
}
