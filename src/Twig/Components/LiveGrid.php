<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Twig\Components;

use Quorae\GridBundle\Contract\BulkActor;
use Quorae\GridBundle\Dto\BulkActionResult;
use Quorae\GridBundle\Dto\GridView;
use Quorae\GridBundle\Enum\BulkActionErrorKind;
use Quorae\GridBundle\Exception\BulkActionException;
use Quorae\GridBundle\Handler\BulkActionExecutor;
use Quorae\GridBundle\Handler\PageParser;
use Quorae\GridBundle\Handler\RenderGridHandler;
use Quorae\GridBundle\Registry\GridRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Live Component — renders a grid in **reactive** mode : every filter /
 * search / page / sort change is a Live-Component round-trip that
 * re-invokes {@see RenderGridHandler::handle()} without a full request.
 *
 * State is carried by `$gridName` + `$q` + `$criteria` + `$page` + `$sort`
 * — exposed as {@see LiveProp} so the template can `data-model` them.
 *
 * **Bulk actions** — when the grid declares `#[BulkAction]` attributes, the
 * user picks rows through row-level checkboxes (wired to `$selectedIds` via
 * `data-model`) and triggers an action through the {@see self::executeBulk()}
 * Live action. The component then :
 *  - resolves the {@see BulkActionDefinition} on the grid ;
 *  - enforces `$requiredRole` via {@see Security::isGranted()} ;
 *  - looks up the handler in the {@see BulkActionHandlerRegistry} ;
 *  - invokes it with a {@see BulkActionRequest} carrying the selection,
 *    `$extraContext`, and the current {@see BulkActor} ;
 *  - surfaces a flash summary through `$flashMessage` / `$flashType` and
 *    wipes the selection on success.
 *
 * Navigation mutations reset `$selectedIds` and `$expandedRowId` through
 * per-prop `onUpdated` hooks — stale state never leaks across pages. A
 * filter/search/sort change additionally restarts pagination at page 1
 * ({@see self::onFilterChanged()}); a `$page` change keeps the page
 * ({@see self::resetSelectionOnNavigation()}).
 *
 * Template path : `@QuoraeGrid/components/Grid/live_grid.html.twig`.
 */
#[AsLiveComponent(name: 'Grid:LiveGrid', template: '@QuoraeGrid/components/Grid/live_grid.html.twig')]
final class LiveGrid
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $gridName = '';

    #[LiveProp(writable: true, url: true, onUpdated: 'onFilterChanged')]
    public ?string $q = null;

    /** @var array<string, mixed> */
    #[LiveProp(writable: true, url: true, onUpdated: 'onFilterChanged')]
    public array $criteria = [];

    #[LiveProp(writable: true, url: new UrlMapping(as: PageParser::PAGE_PARAM), onUpdated: 'resetSelectionOnNavigation')]
    public int $page = 1;

    #[LiveProp(writable: true, url: true, onUpdated: 'onFilterChanged')]
    public ?string $sort = null;

    /** @var array<string, mixed> */
    #[LiveProp]
    public array $extraContext = [];

    /** @var list<int|string> */
    #[LiveProp(writable: true)]
    public array $selectedIds = [];

    #[LiveProp(writable: true)]
    public ?string $expandedRowId = null;

    #[LiveProp]
    public ?string $flashMessage = null;

    /**
     * One of `success` / `warning` / `error` / `info` — template uses this to
     * pick the banner styling.
     */
    #[LiveProp]
    public ?string $flashType = null;

    public function __construct(
        private readonly RenderGridHandler $renderer,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly BulkActionExecutor $bulkActionExecutor,
        private readonly GridRegistry $grids,
    ) {
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    public function mount(string $gridName, array $extraContext = []): void
    {
        $this->gridName = $gridName;
        $this->extraContext = $extraContext;
        $this->criteria = LiveGridCriteria::seed($this->criteria, $this->grids->get($gridName));
    }

    /**
     * Called by the template on every render — re-invokes the handler
     * with the current `LiveProp` state translated back into a synthetic
     * {@see Request}.
     */
    #[ExposeInTemplate]
    public function view(): GridView
    {
        return $this->renderer->handle(
            gridName: $this->gridName,
            request: $this->buildRequest(),
            extraContext: $this->extraContext,
        );
    }

    #[LiveAction]
    public function sort(#[LiveArg] string $column, #[LiveArg] string $direction): void
    {
        $this->sort = $column.':'.$direction;
        $this->page = 1;
        $this->resetNavigationState();
    }

    /**
     * Pills filter handler — invoked by `_filter_live.html.twig` on every Pill
     * click. Mutates the corresponding `$criteria` slot (or unsets it for the
     * "Toutes" pill, which posts an empty value), then resets pagination and
     * selection so navigation never carries stale state.
     */
    #[LiveAction]
    public function setFilter(#[LiveArg] string $property, #[LiveArg] string $value): void
    {
        if ($value === '') {
            unset($this->criteria[$property]);
        } else {
            $this->criteria[$property] = $value;
        }
        $this->page = 1;
        $this->resetNavigationState();
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
        $this->resetNavigationState();
    }

    #[LiveAction]
    public function clearFilters(): void
    {
        $this->criteria = [];
        $this->q = null;
        $this->page = 1;
        $this->resetNavigationState();
    }

    #[LiveAction]
    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    /**
     * @param list<int|string> $ids
     */
    #[LiveAction]
    public function selectAllOnPage(#[LiveArg] array $ids): void
    {
        $this->selectedIds = array_values(array_unique([...$this->selectedIds, ...$ids]));
    }

    #[LiveAction]
    public function executeBulk(#[LiveArg] string $actionName): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof BulkActor) {
            throw new \LogicException('LiveGrid::executeBulk requires an authenticated user implementing ' . BulkActor::class . ' — the Live Component endpoint must be firewall-protected.');
        }

        try {
            $result = $this->bulkActionExecutor->execute(
                gridName: $this->gridName,
                actionName: $actionName,
                selectedIds: $this->selectedIds,
                extraContext: $this->extraContext,
                actor: $user,
            );
            $this->applyFlashFromResult($result);
            $this->selectedIds = [];
        } catch (BulkActionException $exception) {
            $this->flashType = $this->flashTypeForException($exception);
            $this->flashMessage = $exception->getMessage();
            if ($this->flashType === 'error') {
                $this->selectedIds = [];
            }
        }
    }

    /**
     * Hook invoked by Live Component when `$page` changes — drops the current
     * selection and expanded row so stale state never leaks across pages.
     * Must NOT touch `$page` itself (it would defeat pagination).
     */
    public function resetSelectionOnNavigation(): void
    {
        $this->resetNavigationState();
    }

    /**
     * Hook invoked when `$q` / `$criteria` / `$sort` change via a raw
     * `data-model` write (search box, Select, DateRange). Any of these is a
     * fresh query, so pagination must restart at page 1 — mirroring the
     * setFilter / clearFilters / sort Live actions, which a `data-model`
     * write bypasses.
     */
    public function onFilterChanged(): void
    {
        $this->page = 1;
        $this->resetNavigationState();
    }

    /**
     * Clears ephemeral UI state that becomes stale after any navigation
     * mutation (page, search, filter, sort).
     */
    private function resetNavigationState(): void
    {
        $this->selectedIds = [];
        $this->expandedRowId = null;
    }

    private function applyFlashFromResult(BulkActionResult $result): void
    {
        $this->flashType = $result->failureCount === 0 ? 'success' : 'warning';
        $this->flashMessage = $result->successMessage !== ''
            ? $result->successMessage
            : \sprintf('%d ligne(s) traitée(s).', $result->successCount);
    }

    private function flashTypeForException(BulkActionException $exception): string
    {
        return match ($exception->kind) {
            BulkActionErrorKind::EmptySelection => 'warning',
            BulkActionErrorKind::SelectionTooLarge,
            BulkActionErrorKind::OwnershipRejected,
            BulkActionErrorKind::AccessDenied,
            BulkActionErrorKind::UnknownAction => 'error',
            BulkActionErrorKind::HandlerNotTagged,
            BulkActionErrorKind::ValidatorNotTagged => throw $exception,
        };
    }

    private function buildRequest(): Request
    {
        $query = [
            PageParser::PAGE_PARAM => (string) $this->page,
            'criteria' => $this->criteria,
        ];
        if ($this->q !== null) {
            $query['q'] = $this->q;
        }
        if ($this->sort !== null) {
            $query['sort'] = $this->sort;
        }

        $originalRequest = $this->requestStack->getCurrentRequest();
        if ($originalRequest === null) {
            return Request::create('/', 'GET', $query);
        }

        return $originalRequest->duplicate($query);
    }
}
