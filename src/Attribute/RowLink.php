<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

/**
 * Marks a grid class as having clickable rows that navigate to a detail/edit page.
 *
 * Orthogonal to `#[AsGrid]` — decorates the same grid class independently.
 * The framework extracts this attribute in {@see \Quorae\GridBundle\Definition\GridDefinitionResolver}
 * and stores it in {@see \Quorae\GridBundle\Definition\GridDefinition::$rowLink}.
 *
 * The Twig template emits `data-href` + `data-controller="row-link"` on each `<tr>`.
 * When `$frame` is set, `data-row-link-frame-value` is also emitted — the Stimulus
 * controller then loads the URL into the named Turbo Frame instead of navigating.
 *
 * Example (full-page navigation):
 * ```php
 * #[RowLink(route: 'app_client_show')]
 * ```
 *
 * Example (frame / modal):
 * ```php
 * #[RowLink(route: 'app_client_edit', frame: 'modal')]
 * ```
 *
 * Example (multi-param route):
 * ```php
 * #[RowLink(route: 'app_project_edit', params: ['clientId' => 'clientId', 'id' => 'id'], frame: 'modal')]
 * ```
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class RowLink
{
    /**
     * @param string              $route          Symfony route name
     * @param string              $param          Single route parameter name (default 'id') — ignored when $params is non-empty
     * @param string              $rowProperty    Single row DTO property (default 'id') — ignored when $params is non-empty
     * @param array<string,string> $params        Multi-param map: route parameter name => row DTO property name
     * @param string|null         $frame          Turbo Frame id to load into (null = full-page navigation)
     * @param string|null         $ariaLabel      sprintf-format for aria-label (e.g. 'Voir %s')
     * @param string|null         $ariaLabelField Row DTO property injected into $ariaLabel via %s
     */
    public function __construct(
        public string $route,
        public string $param = 'id',
        public string $rowProperty = 'id',
        public array $params = [],
        public ?string $frame = null,
        public ?string $ariaLabel = null,
        public ?string $ariaLabelField = null,
    ) {
    }

    /**
     * Resolves route parameters from a row object.
     *
     * @return array<string, string> Route parameter name => resolved value
     */
    public function getRouteParamNames(): array
    {
        if ($this->params !== []) {
            return $this->params;
        }

        return [$this->param => $this->rowProperty];
    }
}
