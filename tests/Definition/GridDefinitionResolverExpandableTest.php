<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Definition;

use Quorae\GridBundle\Definition\GridDefinitionResolver;
use Quorae\GridBundle\Exception\InvalidGridDefinitionException;
use Quorae\GridBundle\Tests\Fixtures\ExpandableGrid;
use Quorae\GridBundle\Tests\Fixtures\ExpandableWithoutRouteGrid;
use Quorae\GridBundle\Tests\Fixtures\ExpandableWithRowLinkGrid;
use Quorae\GridBundle\Tests\Fixtures\MinimalGrid;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for expandable row support in {@see GridDefinitionResolver}.
 *
 * Models the pattern of GridDefinitionResolverRowLinkTest — one fixture per
 * scenario, each exercising a single validation rule.
 */
final class GridDefinitionResolverExpandableTest extends TestCase
{
    private GridDefinitionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new GridDefinitionResolver();
    }

    public function testExpandableGridResolvesCorrectly(): void
    {
        $definition = $this->resolver->resolve(ExpandableGrid::class);

        self::assertTrue($definition->expandable);
        self::assertSame('test_expand_route', $definition->expandRoute);
        self::assertSame('rowId', $definition->expandRouteParam);
    }

    public function testExpandableWithoutRouteThrows(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('expandRoute');

        $this->resolver->resolve(ExpandableWithoutRouteGrid::class);
    }

    public function testExpandableWithRowLinkThrows(): void
    {
        $this->expectException(InvalidGridDefinitionException::class);
        $this->expectExceptionMessage('mutually exclusive');

        $this->resolver->resolve(ExpandableWithRowLinkGrid::class);
    }

    public function testNonExpandableGridIgnoresExpandFields(): void
    {
        $definition = $this->resolver->resolve(MinimalGrid::class);

        self::assertFalse($definition->expandable);
        self::assertNull($definition->expandRoute);
        self::assertSame('id', $definition->expandRouteParam);
    }
}
