<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

use Quorae\GridBundle\Contract\GridDataSource;
use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Dto\GridResponse;
use Quorae\GridBundle\Dto\GridView;
use Quorae\GridBundle\Dto\Page;
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
final class RenderGridHandler
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
        $runtimeDefinition = $this->resolveRuntimeChoices($definition, $filter, $extraContext);
        $page = $this->pageParser->parse($definition, $request);

        $response = $dataSource->fetch($filter, $page);
        $response = $this->clampToLastPage($dataSource, $filter, $page, $response);

        return new GridView(
            definition: $runtimeDefinition,
            response: $response,
            filter: $filter,
            sort: $page->sort,
            frameId: \sprintf('grid-%s', $definition->name),
            pageParam: PageParser::PAGE_PARAM,
        );
    }

    /**
     * Re-clamps an out-of-range page (spec §9).
     *
     * `PageParser` cannot clamp to `[1, totalPages]` because `totalPages` is
     * unknown until the data source returns. When the response carries a
     * known `totalPages` and the requested page overshoots it, re-fetch once
     * at the last page so the rows and the paginator agree — instead of
     * surfacing an empty grid.
     *
     * PrevNext data sources report `totalPages = null` (no `COUNT(*)`) and are
     * left untouched; Timeline reports `totalPages = 1` with the page already
     * floored to 1 by `PageParser`, so the guard never triggers there either.
     */
    private function clampToLastPage(
        GridDataSource $dataSource,
        object $filter,
        Page $page,
        GridResponse $response,
    ): GridResponse {
        $totalPages = $response->totalPages;
        if ($totalPages === null) {
            return $response;
        }

        // An empty Offset result legitimately reports totalPages = 0; its
        // valid "last page" is page 1 (which shows zero rows). Flooring to 1
        // also keeps `new Page()` valid (it requires number >= 1).
        $lastPage = max(1, $totalPages);
        if ($page->number <= $lastPage) {
            return $response;
        }

        $clampedPage = new Page(
            number: $lastPage,
            limit: $page->limit,
            sort: $page->sort,
        );

        return $dataSource->fetch($filter, $clampedPage);
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
    private function resolveRuntimeChoices(GridDefinition $definition, object $filter, array $extraContext): GridDefinition
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
