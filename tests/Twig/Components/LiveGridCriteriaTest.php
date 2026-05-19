<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Twig\Components;

use Quorae\GridBundle\Definition\FilterDefinition;
use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Enum\FilterType;
use Quorae\GridBundle\Enum\Pagination;
use Quorae\GridBundle\Twig\Components\LiveGridCriteria;
use PHPUnit\Framework\TestCase;

/**
 * Symfony UX `live_controller` throws `Invalid model name` when a
 * `data-model` path is absent from the component's initial props. The
 * Live filter bar binds `criteria[{prop}_from]` / `criteria[{prop}_to]`
 * for DateRange filters — keys that were never seeded — so Live DateRange
 * was inert (GRID-05). {@see LiveGridCriteria::seed()} must expose an
 * (empty) slot for every key the bar can write.
 */
final class LiveGridCriteriaTest extends TestCase
{
    private function definition(FilterDefinition ...$filters): GridDefinition
    {
        return new GridDefinition(
            name: 'fixture',
            dataSource: 'ds',
            filterClass: 'F',
            pagination: Pagination::PrevNext,
            perPage: 50,
            interactive: true,
            emptyMessage: '—',
            renderRow: null,
            columns: [],
            filters: array_values($filters),
            search: null,
            rowSignatures: [],
        );
    }

    private function filter(string $propertyName, FilterType $type): FilterDefinition
    {
        return new FilterDefinition(
            propertyName: $propertyName,
            type: $type,
            label: $propertyName,
            choices: [],
            choicesProvider: null,
        );
    }

    public function testDateRangeFilterSeedsFromAndToKeys(): void
    {
        $criteria = LiveGridCriteria::seed(
            [],
            $this->definition($this->filter('period', FilterType::DateRange)),
        );

        self::assertArrayHasKey('period_from', $criteria);
        self::assertArrayHasKey('period_to', $criteria);
        self::assertSame('', $criteria['period_from']);
        self::assertSame('', $criteria['period_to']);
        self::assertArrayNotHasKey('period', $criteria);
    }

    public function testSelectAndToggleFilterSeedPropertyNameKey(): void
    {
        $criteria = LiveGridCriteria::seed([], $this->definition(
            $this->filter('status', FilterType::Select),
            $this->filter('revisedOnly', FilterType::Toggle),
        ));

        self::assertSame(['status' => '', 'revisedOnly' => ''], $criteria);
    }

    public function testExistingValuesArePreserved(): void
    {
        $criteria = LiveGridCriteria::seed(
            ['period_from' => '2026-01-01', 'status' => 'draft'],
            $this->definition(
                $this->filter('period', FilterType::DateRange),
                $this->filter('status', FilterType::Select),
            ),
        );

        self::assertSame('2026-01-01', $criteria['period_from']);
        self::assertSame('', $criteria['period_to']);
        self::assertSame('draft', $criteria['status']);
    }

    public function testUnknownExtraKeysAreLeftUntouched(): void
    {
        $criteria = LiveGridCriteria::seed(
            ['clientId' => '7'],
            $this->definition($this->filter('status', FilterType::Select)),
        );

        self::assertSame('7', $criteria['clientId']);
        self::assertSame('', $criteria['status']);
    }
}
