<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Attribute;

/**
 * Marks a grid class as having clickable rows that navigate to a detail page.
 *
 * Orthogonal to `#[AsGrid]` — decorates the same grid class independently.
 * The framework extracts this attribute in {@see \Quorae\GridBundle\Definition\GridDefinitionResolver}
 * and stores it in {@see \Quorae\GridBundle\Definition\GridDefinition::$rowLink}.
 *
 * The Twig template then emits `data-href` + `data-controller="row-link"` on
 * each `<tr>` ; the Stimulus controller handles the actual navigation.
 *
 * Example :
 * ```php
 * #[AsGrid(name: 'clients', dataSource: ClientRepository::class)]
 * #[RowLink(route: 'app_client_show', param: 'id', rowProperty: 'id', ariaLabel: 'Voir la fiche de %s', ariaLabelField: 'raisonSociale')]
 * final class ClientsGrid { … }
 * ```
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class RowLink
{
    /**
     * @param string      $route          Symfony route name (e.g. 'app_client_show')
     * @param string      $param          Route parameter name (default 'id')
     * @param string      $rowProperty    Row DTO property name whose value fills `$param` (default 'id')
     * @param string|null $ariaLabel      sprintf-format string for `aria-label` (e.g. 'Voir la fiche de %s') — null disables it
     * @param string|null $ariaLabelField Row DTO property name whose value is injected into `$ariaLabel` via %s
     */
    public function __construct(
        public string $route,
        public string $param = 'id',
        public string $rowProperty = 'id',
        public ?string $ariaLabel = null,
        public ?string $ariaLabelField = null,
    ) {
    }
}
