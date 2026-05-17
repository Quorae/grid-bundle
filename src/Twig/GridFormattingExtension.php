<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filters required by the bundle's cell partials so the framework is
 * standalone — it ships its own formatting helpers instead of depending on
 * host-side Twig extensions.
 *
 * `montant_fr` : formate un montant en euros avec virgule décimale et
 * espace insécable (U+00A0) comme séparateur de milliers. Les valeurs
 * nulles ou nulles-équivalentes (0, '0', '0.00', 0.0) sont rendues par
 * un tiret cadratin pour préserver la lisibilité des tables denses.
 * Consommé par `@QuoraeGrid/components/Grid/cell/_montant_fr.html.twig`.
 *
 * `enum_value` : extrait la valeur scalaire d'un BackedEnum — indispensable
 * quand les valeurs d'enum servent de clés de tableau ou de comparaisons de
 * chaînes dans les templates (`filter.choices[severity]`). Consommé par
 * `_filter_turbo.html.twig` / `_filter_live.html.twig`.
 */
final class GridFormattingExtension extends AbstractExtension
{
    private const string EM_DASH = '—';
    private const string NBSP = "\u{00A0}";

    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('montant_fr', $this->montantFr(...)),
            new TwigFilter('enum_value', self::extractValue(...)),
        ];
    }

    public function montantFr(int|float|string|null $value): string
    {
        if ($value === null) {
            return self::EM_DASH;
        }

        $amount = (float) $value;

        if ($amount === 0.0) {
            return self::EM_DASH;
        }

        return number_format($amount, 2, ',', self::NBSP);
    }

    private static function extractValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
