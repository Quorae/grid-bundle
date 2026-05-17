<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Definition;

use Quorae\GridBundle\Attribute\AsGrid;
use Quorae\GridBundle\Attribute\Column;
use Quorae\GridBundle\Attribute\RowLink;
use Quorae\GridBundle\Definition\GridDefinitionResolver;
use Quorae\GridBundle\Tests\Fixtures\InMemoryDataSource;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the `#[RowLink]` attribute extraction in
 * {@see GridDefinitionResolver}.
 *
 * RED tests written first — `RowLink` attribute and `GridDefinition::$rowLink`
 * do not exist yet.
 */
final class GridDefinitionResolverRowLinkTest extends TestCase
{
    private GridDefinitionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new GridDefinitionResolver();
    }

    public function testResolvesRowLinkWhenAttributeIsPresentOnGridClass(): void
    {
        $definition = $this->resolver->resolve(GridWithRowLinkFixture::class);

        self::assertNotNull($definition->rowLink);
        self::assertSame('app_client_show', $definition->rowLink->route);
        self::assertSame('id', $definition->rowLink->param);
        self::assertSame('id', $definition->rowLink->rowProperty);
        self::assertSame('Voir la fiche de %s', $definition->rowLink->ariaLabel);
        self::assertSame('raisonSociale', $definition->rowLink->ariaLabelField);
    }

    public function testRowLinkIsNullWhenAttributeIsAbsent(): void
    {
        $definition = $this->resolver->resolve(GridWithoutRowLinkFixture::class);

        self::assertNull($definition->rowLink);
    }

    public function testRowLinkWithMinimalParams(): void
    {
        $definition = $this->resolver->resolve(GridWithMinimalRowLinkFixture::class);

        self::assertNotNull($definition->rowLink);
        self::assertSame('app_dossier_show', $definition->rowLink->route);
        self::assertSame('id', $definition->rowLink->param);
        self::assertSame('id', $definition->rowLink->rowProperty);
        self::assertNull($definition->rowLink->ariaLabel);
        self::assertNull($definition->rowLink->ariaLabelField);
    }
}

// --- inline fixtures ---

/** @internal */
#[AsGrid(
    name: 'fixture_row_link',
    dataSource: InMemoryDataSource::class,
)]
#[RowLink(route: 'app_client_show', param: 'id', rowProperty: 'id', ariaLabel: 'Voir la fiche de %s', ariaLabelField: 'raisonSociale')]
final class GridWithRowLinkFixture
{
    #[Column(label: 'Raison sociale')]
    public string $raisonSociale = '';
}

/** @internal */
#[AsGrid(
    name: 'fixture_no_row_link',
    dataSource: InMemoryDataSource::class,
)]
final class GridWithoutRowLinkFixture
{
    #[Column(label: 'Code')]
    public string $code = '';
}

/** @internal */
#[AsGrid(
    name: 'fixture_minimal_row_link',
    dataSource: InMemoryDataSource::class,
)]
#[RowLink(route: 'app_dossier_show')]
final class GridWithMinimalRowLinkFixture
{
    #[Column(label: 'Code')]
    public string $code = '';
}
