<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Twig;

use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Definition\RowSignatureDefinition;
use Quorae\GridBundle\Enum\LedgerSignature;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension backing the grid framework's rendering concerns.
 *
 * `grid_row_classes(row, definition)` invokes every `#[RowSignature]`
 * static predicate declared on the grid class, collects the Ledger
 * signatures that apply to the current row, and returns the concatenated
 * CSS classes — `"anomaly"` / `"total"` / `"subtotal"`.
 *
 * The grid's row objects never need to expose a `ledgerSignatures()`
 * method : the framework reads the predicates straight from the grid
 * definition. Keeps row DTOs free of presentation concerns.
 */
final class GridExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('grid_row_classes', $this->rowClasses(...)),
        ];
    }

    public function rowClasses(object $row, GridDefinition $definition): string
    {
        $classes = [];
        foreach ($definition->rowSignatures as $signatureDefinition) {
            if (!$this->signatureApplies($row, $signatureDefinition)) {
                continue;
            }
            $classes[] = $this->cssClassFor($signatureDefinition->signature);
        }

        if ($classes === []) {
            return '';
        }

        return implode(' ', array_unique($classes));
    }

    private function signatureApplies(object $row, RowSignatureDefinition $definition): bool
    {
        [$class, $method] = $definition->callable;
        if (!\is_callable([$class, $method])) {
            return false;
        }

        /** @var callable(object): bool $callable */
        $callable = [$class, $method];

        return (bool) $callable($row);
    }

    private function cssClassFor(LedgerSignature $signature): string
    {
        return match ($signature) {
            LedgerSignature::AnomalyBar => 'anomaly',
            LedgerSignature::TotalRule => 'total',
            LedgerSignature::Subtotal => 'subtotal',
        };
    }
}
