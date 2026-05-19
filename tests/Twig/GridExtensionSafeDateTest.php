<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Twig;

use Quorae\GridBundle\Twig\GridExtension;
use PHPUnit\Framework\TestCase;

final class GridExtensionSafeDateTest extends TestCase
{
    private GridExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new GridExtension();
    }

    public function testFormatsDateTimeInterface(): void
    {
        $date = new \DateTimeImmutable('2026-03-15');

        self::assertSame('15/03', $this->extension->gridSafeDate($date, 'd/m'));
    }

    public function testFormatsParseableString(): void
    {
        self::assertSame('31/12', $this->extension->gridSafeDate('2026-12-31', 'd/m'));
    }

    public function testReturnsRawStringWhenUnparseable(): void
    {
        self::assertSame('notadate', $this->extension->gridSafeDate('notadate', 'd/m'));
    }

    public function testDoesNotThrowOnOutOfRangeDateString(): void
    {
        self::assertSame('2026-13-99', $this->extension->gridSafeDate('2026-13-99', 'd/m'));
    }

    public function testReturnsPlaceholderForNull(): void
    {
        self::assertSame('…', $this->extension->gridSafeDate(null, 'd/m'));
    }

    public function testReturnsPlaceholderForEmptyString(): void
    {
        self::assertSame('…', $this->extension->gridSafeDate('', 'd/m'));
    }

    public function testSupportsIsoOutputFormat(): void
    {
        self::assertSame('2026-03-15', $this->extension->gridSafeDate('2026-03-15', 'Y-m-d'));
    }

    public function testFilterIsRegistered(): void
    {
        $names = array_map(
            static fn (\Twig\TwigFilter $filter): string => $filter->getName(),
            $this->extension->getFilters(),
        );

        self::assertContains('grid_safe_date', $names);
    }
}
