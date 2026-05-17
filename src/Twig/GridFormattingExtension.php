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
 *
 * `safe_date_fr` : formate une valeur date pour les chips DateRange sans
 * jamais lever d'exception. Les params DateRange du Filter-DTO sont typés
 * `?string` (cf. design §8.A6) : la valeur reçue ici est la chaîne brute de
 * la requête. Un `|date()` nu sur une chaîne non-parseable lèverait
 * `DateMalformedStringException` (PHP 8.3+) → RuntimeError Twig → HTTP 500.
 * Ce filtre dégrade gracieusement (cf. §9 « valeur de filtre non-coercible
 * → défaut, pas de 500 ») : valeur parseable → date formatée, chaîne non
 * parseable → chaîne brute rendue telle quelle, vide/null → tiret de
 * substitution. Consommé par `_filter_bar.html.twig` (chips DateRange).
 */
final class GridFormattingExtension extends AbstractExtension
{
    private const string EM_DASH = '—';
    private const string NBSP = "\u{00A0}";
    private const string DATE_PLACEHOLDER = '…';

    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('montant_fr', $this->montantFr(...)),
            new TwigFilter('enum_value', self::extractValue(...)),
            new TwigFilter('safe_date_fr', $this->safeDateFr(...)),
        ];
    }

    /**
     * Formate une valeur date sans jamais lever d'exception.
     *
     * Contrat de dégradation gracieuse (cf. design §9) :
     *  - {@see \DateTimeInterface} → formatée selon `$format`
     *  - chaîne non vide parseable → formatée selon `$format`
     *  - chaîne non vide non parseable → rendue telle quelle (jamais de 500)
     *  - null / chaîne vide → tiret de substitution
     */
    public function safeDateFr(\DateTimeInterface|string|null $value, string $format): string
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

    public function montantFr(int|float|string|null $value): string
    {
        if ($value === null) {
            return self::EM_DASH;
        }

        if (!\is_numeric($value)) {
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
