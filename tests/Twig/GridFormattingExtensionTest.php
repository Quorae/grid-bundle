<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Twig;

use Quorae\GridBundle\Twig\GridFormattingExtension;
use PHPUnit\Framework\TestCase;

final class GridFormattingExtensionTest extends TestCase
{
    private GridFormattingExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new GridFormattingExtension();
    }

    public function testSafeDateFrFormatsDateTimeInterface(): void
    {
        $date = new \DateTimeImmutable('2026-03-15');

        self::assertSame('15/03', $this->extension->safeDateFr($date, 'd/m'));
    }

    public function testSafeDateFrFormatsParseableStringWithGivenFormat(): void
    {
        self::assertSame('31/12', $this->extension->safeDateFr('2026-12-31', 'd/m'));
    }

    public function testSafeDateFrReturnsRawStringWhenUnparseableInsteadOfThrowing(): void
    {
        self::assertSame('notadate', $this->extension->safeDateFr('notadate', 'd/m'));
    }

    public function testSafeDateFrDoesNotThrowOnOutOfRangeDateString(): void
    {
        self::assertSame('2026-13-99', $this->extension->safeDateFr('2026-13-99', 'd/m'));
    }

    public function testSafeDateFrReturnsPlaceholderForNull(): void
    {
        self::assertSame('…', $this->extension->safeDateFr(null, 'd/m'));
    }

    public function testSafeDateFrReturnsPlaceholderForEmptyString(): void
    {
        self::assertSame('…', $this->extension->safeDateFr('', 'd/m'));
    }

    public function testSafeDateFrSupportsIsoOutputFormat(): void
    {
        self::assertSame('2026-03-15', $this->extension->safeDateFr('2026-03-15', 'Y-m-d'));
    }

    public function testSafeDateFrFilterIsRegistered(): void
    {
        $names = array_map(
            static fn (\Twig\TwigFilter $filter): string => $filter->getName(),
            $this->extension->getFilters(),
        );

        self::assertContains('safe_date_fr', $names);
    }
}
