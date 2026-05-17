<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Dto\GridView;
use Quorae\GridBundle\Exception\MissingDataSourceException;
use Quorae\GridBundle\Registry\GridRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Single entry-point of the grid rendering pipeline.
 *
 * Flow :
 *  1. Resolve the {@see GridDefinition} from the {@see GridRegistry}.
 *  2. Delegate filter DTO hydration to {@see FilterHydrator}.
 *  3. Delegate pagination + sort parsing to {@see PageParser}.
 *  4. Resolve the data-source service from the injected locator and
 *     invoke `fetch()`.
 *  5. Wrap everything in a {@see GridView}.
 *
 * **Stateless by design** — same instance can serve concurrent requests.
 */
/**
 * Non-`final`, non-`readonly` on purpose : unit tests extend and stub
 * `handle()` (Test Subclass pattern) for consumers that only need the
 * rendered `GridView` without exercising filter hydration or data-source
 * resolution. Immutability is preserved by making each dependency `readonly`
 * on the property declaration.
 */
class RenderGridHandler
{
    public function __construct(
        private readonly GridRegistry $registry,
        private readonly ContainerInterface $dataSources,
        private readonly ContainerInterface $choicesProviders,
        private readonly FilterHydrator $filterHydrator,
        private readonly PageParser $pageParser,
    ) {
    }

    /**
     * @param array<string, mixed> $extraContext overrides for filter properties
     *                                           that are never exposed in the
     *                                           query string (e.g. `clientId`
     *                                           for an ACD grid)
     */
    public function handle(string $gridName, Request $request, array $extraContext = []): GridView
    {
        $definition = $this->registry->get($gridName);
        $dataSource = $this->resolveDataSource($definition);
        $filter = $this->filterHydrator->hydrate($definition, $request, $extraContext);
        $runtimeDefinition = $this->resolveRuntimeDefinition($definition, $filter, $extraContext);
        $page = $this->pageParser->parse($definition, $request);

        $response = $dataSource->fetch($filter, $page);

        return new GridView(
            definition: $runtimeDefinition,
            response: $response,
            filter: $filter,
            sort: $page->sort,
            frameId: \sprintf('grid-%s', $definition->name),
            pageParam: PageParser::PAGE_PARAM,
        );
    }

    private function resolveDataSource(GridDefinition $definition): GridDataSource
    {
        if (!$this->dataSources->has($definition->dataSource)) {
            throw MissingDataSourceException::forGrid($definition->name, $definition->dataSource);
        }
        /** @var GridDataSource $service */
        $service = $this->dataSources->get($definition->dataSource);

        return $service;
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function resolveRuntimeDefinition(GridDefinition $definition, object $filter, array $extraContext): GridDefinition
    {
        $resolvedFilters = [];
        $hasRuntimeChoices = false;

        foreach ($definition->filters as $filterDefinition) {
            if ($filterDefinition->choicesProvider === null) {
                $resolvedFilters[] = $filterDefinition;
                continue;
            }

            if (!$this->choicesProviders->has($filterDefinition->choicesProvider)) {
                throw new \LogicException(\sprintf('Grid "%s" declares choices provider "%s" for filter "%s", but no such service is registered.', $definition->name, $filterDefinition->choicesProvider, $filterDefinition->propertyName));
            }

            $provider = $this->choicesProviders->get($filterDefinition->choicesProvider);
            $resolvedFilters[] = $filterDefinition->withChoices($provider->getChoices($filter, $extraContext));
            $hasRuntimeChoices = true;
        }

        if (!$hasRuntimeChoices) {
            return $definition;
        }

        return $definition->withFilters($resolvedFilters);
    }
}
