<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Dto;

use Quorae\GridBundle\Dto\GridResponse;
use PHPUnit\Framework\TestCase;

final class GridResponseTest extends TestCase
{
    public function testStoresAllFields(): void
    {
        $rows = [new \stdClass(), new \stdClass()];
        $response = new GridResponse(rows: $rows, hasNext: true, hasPrev: false, page: 1);

        self::assertSame($rows, $response->rows);
        self::assertTrue($response->hasNext);
        self::assertFalse($response->hasPrev);
        self::assertSame(1, $response->page);
    }

    public function testTotalPagesNullByDefault(): void
    {
        $response = new GridResponse(rows: [], hasNext: false, hasPrev: false, page: 1);

        self::assertNull($response->totalPages);
    }

    public function testTotalPagesStored(): void
    {
        $response = new GridResponse(rows: [], hasNext: true, hasPrev: false, page: 2, totalPages: 5);

        self::assertSame(5, $response->totalPages);
    }

    public function testRejectsNegativePage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new GridResponse(rows: [], hasNext: false, hasPrev: false, page: 0);
    }

    /**
     * Delta A (port-map §2.A.2) : `GridResponse` gains `?int $totalCount`,
     * default `null`. Pre-existing `totalPages` / `groupCounts` are untouched.
     */
    public function testTotalCountNullByDefault(): void
    {
        $response = new GridResponse(rows: [], hasNext: false, hasPrev: false, page: 1);

        self::assertNull($response->totalCount);
    }

    public function testTotalCountStoredViaNamedArgument(): void
    {
        $response = new GridResponse(rows: [], hasNext: false, hasPrev: false, page: 1, totalCount: 137);

        self::assertSame(137, $response->totalCount);
        self::assertNull($response->totalPages);
    }

    public function testTotalCountAndTotalPagesCoexist(): void
    {
        $response = new GridResponse(
            rows: [],
            hasNext: true,
            hasPrev: false,
            page: 2,
            totalCount: 137,
            totalPages: 3,
        );

        self::assertSame(137, $response->totalCount);
        self::assertSame(3, $response->totalPages);
    }

    /**
     * Pins the exact constructor slot order mandated by delta A :
     * `(rows, hasNext, hasPrev, page, groupCounts, totalCount, totalPages)`.
     * Data sources (WP-4/5) construct positionally in places — a slot drift
     * would silently swap totalCount/totalPages, so freeze it here.
     */
    public function testPositionalConstructorSlotOrder(): void
    {
        $response = new GridResponse(
            [],
            true,
            false,
            4,
            ['grp' => 9],
            200,
            10,
        );

        self::assertSame(['grp' => 9], $response->groupCounts);
        self::assertSame(200, $response->totalCount);
        self::assertSame(10, $response->totalPages);
    }
}
