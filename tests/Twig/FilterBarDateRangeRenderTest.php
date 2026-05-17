<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Twig;

use Quorae\GridBundle\Twig\GridFormattingExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;

/**
 * Reproduces, at the Twig-expression boundary, the exact DateRange chip
 * rendering performed by `_filter_bar.html.twig` (lines ~102-103).
 *
 * Per design §8.A6 the DateRange Filter-DTO params are typed `?string`
 * (no date coercion — the data source parses them). The raw request string
 * therefore reaches the template. The pre-fix expression fell back to the
 * native `|date()` filter on a non-`\DateTimeInterface`, so a malformed
 * value (`notadate`, `2026-13-99`, …) raised `DateMalformedStringException`
 * → Twig `RuntimeError` → HTTP 500 on all 4 DateRange grids.
 *
 * These tests assert the post-fix `|safe_date_fr()` pipeline degrades
 * gracefully (no exception, raw string rendered) while valid dates still
 * format and a still-valid sibling bound is unaffected.
 */
final class FilterBarDateRangeRenderTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = new Environment(new ArrayLoader([
            // Faithful copy of the _filter_bar.html.twig chip expression.
            'chip' => "{{ hasFrom ? fromValue|safe_date_fr('d/m') : '…' }}"
                .'|'
                ."{{ hasTo ? toValue|safe_date_fr('d/m') : '…' }}",
            // The native |date() the template used pre-fix — kept to prove
            // the regression really existed for the right reason.
            'legacy' => "{{ fromValue|date('d/m') }}",
        ]));
        $this->twig->addExtension(new GridFormattingExtension());
    }

    public function testNativeDateFilterStillThrowsOnMalformedStringProvingTheRegression(): void
    {
        $this->expectException(RuntimeError::class);

        $this->twig->render('legacy', ['fromValue' => 'notadate']);
    }

    public function testChipDoesNotThrowAndRendersRawStringForMalformedFromBound(): void
    {
        $output = $this->twig->render('chip', [
            'hasFrom' => true,
            'fromValue' => 'notadate',
            'hasTo' => false,
            'toValue' => '',
        ]);

        self::assertSame('notadate|…', $output);
    }

    public function testChipDoesNotThrowOnOutOfRangeDateString(): void
    {
        $output = $this->twig->render('chip', [
            'hasFrom' => true,
            'fromValue' => '2026-13-99',
            'hasTo' => false,
            'toValue' => '',
        ]);

        self::assertSame('2026-13-99|…', $output);
    }

    public function testChipFormatsValidDateStrings(): void
    {
        $output = $this->twig->render('chip', [
            'hasFrom' => true,
            'fromValue' => '2026-03-01',
            'hasTo' => true,
            'toValue' => '2026-03-31',
        ]);

        self::assertSame('01/03|31/03', $output);
    }

    public function testMalformedFromBoundIgnoredWhileValidToBoundStillFormats(): void
    {
        $output = $this->twig->render('chip', [
            'hasFrom' => true,
            'fromValue' => 'garbage',
            'hasTo' => true,
            'toValue' => '2026-12-31',
        ]);

        self::assertSame('garbage|31/12', $output);
    }
}
