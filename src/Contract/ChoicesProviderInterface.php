<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Contract;

/**
 * Produces a choice map for a `#[Filter]` whose allowed values are dynamic
 * (journaux of the current dossier, tiers of the current client, etc.).
 *
 * The map is `label => value` or `value => label` depending on the caller
 * convention — the framework forwards the array as-is to the Twig component
 * layer which renders a `<select>` / pills group.
 */
interface ChoicesProviderInterface
{
    /**
     * @param array<string, mixed> $extraContext
     *
     * @return array<int|string, scalar>
     */
    public function getChoices(object $filter, array $extraContext = []): array;
}
