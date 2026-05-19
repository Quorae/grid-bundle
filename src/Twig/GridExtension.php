<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Twig;

use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Definition\RowSignatureDefinition;
use Quorae\GridBundle\Enum\LedgerSignature;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension backing the grid framework's rendering concerns.
 *
 * Functions:
 *   `grid_row_classes(row, definition)` — evaluates `#[RowSignature]`
 *   predicates and returns concatenated CSS classes.
 *
 * Filters:
 *   `grid_safe_date(value, format)` — safe date formatting for DateRange
 *   filter chips. Degrades gracefully on unparseable strings instead of
 *   throwing DateMalformedStringException.
 */
final class GridExtension extends AbstractExtension
{
    private const string DATE_PLACEHOLDER = '…';

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('grid_row_classes', $this->rowClasses(...)),
        ];
    }

    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('grid_safe_date', $this->gridSafeDate(...)),
        ];
    }

    public function gridSafeDate(\DateTimeInterface|string|null $value, string $format): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        if ($value === null || $value === '') {
            return self::DATE_PLACEHOLDER;
        }

        try {
            return (new \DateTimeImmutable($value))->format($format);
        } catch (\Exception) {
            return $value;
        }
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
