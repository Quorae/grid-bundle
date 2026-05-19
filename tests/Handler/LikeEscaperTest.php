<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Handler;

use Quorae\GridBundle\Handler\LikeEscaper;
use PHPUnit\Framework\TestCase;

final class LikeEscaperTest extends TestCase
{
    public function testContainsWrapsWithPercent(): void
    {
        self::assertSame('%hello%', LikeEscaper::contains('hello'));
    }

    public function testStartsWithAppendsPercent(): void
    {
        self::assertSame('hello%', LikeEscaper::startsWith('hello'));
    }

    public function testExactReturnsEscapedOnly(): void
    {
        self::assertSame('hello', LikeEscaper::exact('hello'));
    }

    public function testEscapesPercentWildcard(): void
    {
        self::assertSame('%50\\%%', LikeEscaper::contains('50%'));
    }

    public function testEscapesUnderscoreWildcard(): void
    {
        self::assertSame('%\\_under%', LikeEscaper::contains('_under'));
    }

    public function testEscapesBackslash(): void
    {
        self::assertSame('%path\\\\file%', LikeEscaper::contains('path\\file'));
    }

    public function testEscapeCharConstant(): void
    {
        self::assertSame('\\', LikeEscaper::ESCAPE_CHAR);
    }
}
