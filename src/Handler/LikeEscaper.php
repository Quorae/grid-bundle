<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Handler;

/**
 * Safe LIKE parameter builder — owns the escape/sanitise pair so the
 * `ESCAPE` clause in the query and the `addcslashes()` call that
 * sanitises the user input are always in sync.
 *
 * Usage in a data source:
 *
 *     $qb->andWhere('t.name LIKE :q ESCAPE ' . $qb->expr()->literal(LikeEscaper::ESCAPE_CHAR))
 *        ->setParameter('q', LikeEscaper::contains($userInput));
 */
final class LikeEscaper
{
    public const string ESCAPE_CHAR = '\\';

    public static function contains(string $value): string
    {
        return '%' . self::escape($value) . '%';
    }

    public static function startsWith(string $value): string
    {
        return self::escape($value) . '%';
    }

    public static function exact(string $value): string
    {
        return self::escape($value);
    }

    public static function escape(string $value): string
    {
        return addcslashes($value, '%_' . self::ESCAPE_CHAR);
    }
}
