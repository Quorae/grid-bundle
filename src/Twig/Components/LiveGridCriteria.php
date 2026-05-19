<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Twig\Components;

use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Enum\FilterType;

/**
 * Seeds the writable `criteria` keys the Live filter bar binds via
 * `data-model`, so Symfony UX LiveComponent's value store accepts every
 * filter write.
 *
 * `_filter_bar.html.twig` binds, in Live mode:
 *   - select / toggle / pills → `criteria[{propertyName}]`
 *   - date_range              → `criteria[{propertyName}_from]`
 *                                `criteria[{propertyName}_to]`
 *
 * `live_controller` rejects a `data-model` whose normalised path is absent
 * from the component's initial props (`valueStore.has()` → "Invalid model
 * name"). DateRange keys carry a `_from`/`_to` suffix that never matched a
 * seeded key, so Live DateRange was inert (GRID-05). Exposing an empty slot
 * for every bound key fixes that without changing server semantics — an
 * empty bound yields no predicate, exactly as an absent one.
 */
final class LiveGridCriteria
{
    /**
     * @param array<string, mixed> $current
     *
     * @return array<string, mixed>
     */
    public static function seed(array $current, GridDefinition $definition): array
    {
        foreach ($definition->filters as $filter) {
            $keys = $filter->type === FilterType::DateRange
                ? [$filter->propertyName . '_from', $filter->propertyName . '_to']
                : [$filter->propertyName];

            foreach ($keys as $key) {
                if (!\array_key_exists($key, $current)) {
                    $current[$key] = '';
                }
            }
        }

        return $current;
    }
}
