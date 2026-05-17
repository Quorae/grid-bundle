<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Handler;

use Quorae\GridBundle\Contract\BulkActionHandler;
use Quorae\GridBundle\Contract\BulkActor;
use Quorae\GridBundle\Contract\BulkOwnershipValidator;
use Quorae\GridBundle\Definition\BulkActionDefinition;
use Quorae\GridBundle\Definition\GridDefinition;
use Quorae\GridBundle\Dto\BulkActionRequest;
use Quorae\GridBundle\Dto\BulkActionResult;
use Quorae\GridBundle\Enum\BulkActionErrorKind;
use Quorae\GridBundle\Enum\Pagination;
use Quorae\GridBundle\Exception\BulkActionException;
use Quorae\GridBundle\Handler\BulkActionExecutor;
use Quorae\GridBundle\Registry\BulkActionHandlerRegistry;
use Quorae\GridBundle\Registry\GridRegistry;
use Quorae\GridBundle\Registry\OwnershipValidatorRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Tests for the extracted {@see BulkActionExecutor} — covers the
 * resolve → authorise → validate → ownership filter → invoke pipeline.
 */
#[AllowMockObjectsWithoutExpectations]
final class BulkActionExecutorTest extends TestCase
{
    public function testExecutesDelegateToHandlerAndReturnsBulkActionResult(): void
    {
        $user = $this->buildUser();
        $expectedResult = new BulkActionResult(2, 0, [], '2 lignes supprimées.');
        $handler = new SpyHandler($expectedResult);

        $executor = $this->buildExecutor(
            gridName: 'memos',
            bulkActions: [
                $this->definition('delete', SpyHandler::class, PassthroughValidator::class),
            ],
            handlers: [SpyHandler::class => $handler],
            validators: [PassthroughValidator::class => new PassthroughValidator()],
            user: $user,
        );

        $result = $executor->execute('memos', 'delete', [11, 12], ['dossier' => 'abc'], $user);

        self::assertSame($expectedResult, $result);
        self::assertNotNull($handler->lastRequest);
        self::assertSame('memos', $handler->lastRequest->gridName);
        self::assertSame('delete', $handler->lastRequest->actionName);
        self::assertSame([11, 12], $handler->lastRequest->rowIds);
        self::assertSame($user, $handler->lastRequest->actor);
        self::assertSame(['dossier' => 'abc'], $handler->lastRequest->extraContext);
    }

    public function testThrowsOnUnknownAction(): void
    {
        $executor = $this->buildExecutor(gridName: 'memos', bulkActions: []);

        $this->expectException(BulkActionException::class);
        $this->expectExceptionMessage('"memos"');

        $executor->execute('memos', 'ghost', [1], [], $this->buildUser());
    }

    public function testThrowsWhenRoleDenied(): void
    {
        $executor = $this->buildExecutor(
            gridName: 'memos',
            bulkActions: [
                $this->definition('delete', SpyHandler::class, PassthroughValidator::class, requiredRole: 'ROLE_ADMIN'),
            ],
            grantedRoles: ['ROLE_USER'],
        );

        $this->expectException(BulkActionException::class);
        $this->expectExceptionMessage('ROLE_ADMIN');

        $executor->execute('memos', 'delete', [1], [], $this->buildUser());
    }

    public function testThrowsOnEmptySelection(): void
    {
        $executor = $this->buildExecutor(
            gridName: 'memos',
            bulkActions: [
                $this->definition('delete', SpyHandler::class, PassthroughValidator::class),
            ],
        );

        $this->expectException(BulkActionException::class);

        $executor->execute('memos', 'delete', [], [], $this->buildUser());
    }

    public function testThrowsOnOversizedSelection(): void
    {
        $executor = $this->buildExecutor(
            gridName: 'memos',
            bulkActions: [
                $this->definition('delete', SpyHandler::class, PassthroughValidator::class),
            ],
        );

        $this->expectException(BulkActionException::class);

        $executor->execute('memos', 'delete', range(1, BulkActionExecutor::MAX_BULK_SELECTION + 1), [], $this->buildUser());
    }

    public function testOwnershipValidatorFiltersIds(): void
    {
        $user = $this->buildUser();
        $expectedResult = new BulkActionResult(1, 0, []);
        $handler = new SpyHandler($expectedResult);

        $executor = $this->buildExecutor(
            gridName: 'memos',
            bulkActions: [
                $this->definition('delete', SpyHandler::class, OddOnlyValidator::class),
            ],
            handlers: [SpyHandler::class => $handler],
            validators: [OddOnlyValidator::class => new OddOnlyValidator()],
        );

        $executor->execute('memos', 'delete', [1, 2, 3, 4], ['dossierId' => 1], $user);

        self::assertNotNull($handler->lastRequest);
        self::assertSame([1, 3], $handler->lastRequest->rowIds);
    }

    public function testOwnershipRejectedWhenAllIdsFiltered(): void
    {
        $user = $this->buildUser();

        $executor = $this->buildExecutor(
            gridName: 'memos',
            bulkActions: [
                $this->definition('delete', SpyHandler::class, RejectAllValidator::class),
            ],
            validators: [RejectAllValidator::class => new RejectAllValidator()],
        );

        try {
            $executor->execute('memos', 'delete', [1, 2], [], $user);
            self::fail('Expected BulkActionException');
        } catch (BulkActionException $e) {
            self::assertSame(BulkActionErrorKind::OwnershipRejected, $e->kind);
            self::assertStringContainsString('2 ligne(s)', $e->getMessage());
        }
    }

    /**
     * @param class-string<BulkActionHandler>      $handler
     * @param class-string<BulkOwnershipValidator> $validator
     */
    private function definition(
        string $name,
        string $handler,
        string $validator,
        string $requiredRole = 'ROLE_USER',
    ): BulkActionDefinition {
        return new BulkActionDefinition(
            name: $name,
            label: ucfirst($name),
            handlerService: $handler,
            ownershipValidator: $validator,
            destructive: true,
            icon: null,
            confirmMessage: null,
            requiredRole: $requiredRole,
        );
    }

    /**
     * @param list<BulkActionDefinition>                  $bulkActions
     * @param array<class-string, BulkActionHandler>      $handlers
     * @param array<class-string, BulkOwnershipValidator> $validators
     * @param list<string>                                $grantedRoles
     */
    private function buildExecutor(
        string $gridName = 'memos',
        array $bulkActions = [],
        array $handlers = [],
        array $validators = [],
        ?BulkActor $user = null,
        array $grantedRoles = ['ROLE_USER', 'ROLE_ADMIN'],
    ): BulkActionExecutor {
        $definition = new GridDefinition(
            name: $gridName,
            dataSource: FakeDataSource::class,
            filterClass: \stdClass::class,
            pagination: Pagination::PrevNext,
            perPage: 50,
            interactive: true,
            emptyMessage: '',
            renderRow: null,
            columns: [],
            filters: [],
            search: null,
            rowSignatures: [],
            defaultSort: null,
            bulkActions: $bulkActions,
            rowIdProperty: 'id',
        );

        $grids = new InMemoryGridRegistryForExecutor([$gridName => $definition]);

        $handlerFactories = [];
        foreach ($handlers as $fqcn => $handler) {
            $handlerFactories[$fqcn] = static fn (): BulkActionHandler => $handler;
        }
        $handlerRegistry = new BulkActionHandlerRegistry(new ServiceLocator($handlerFactories));

        $validatorFactories = [];
        foreach ($validators as $fqcn => $validator) {
            $validatorFactories[$fqcn] = static fn (): BulkOwnershipValidator => $validator;
        }
        $validatorRegistry = new OwnershipValidatorRegistry(new ServiceLocator($validatorFactories));

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static fn ($role): bool => \in_array($role, $grantedRoles, true),
        );

        return new BulkActionExecutor($grids, $handlerRegistry, $validatorRegistry, $security);
    }

    /**
     * Delta B (port-map §2.B) : the bundle is decoupled from any host user
     * entity through the {@see BulkActor} marker. The test supplies a fake
     * actor implementing the marker — exactly how a host (e.g. SimpleCRM's
     * future user entity) would opt in.
     */
    private function buildUser(): BulkActor
    {
        return new FakeActor();
    }
}

/**
 * @internal — minimal {@see BulkActor} stand-in (delta B). The framework
 * never reads anything off the actor; it only forwards the instance.
 */
final class FakeActor implements BulkActor
{
}

/**
 * @internal
 */
final class SpyHandler implements BulkActionHandler
{
    public ?BulkActionRequest $lastRequest = null;

    public function __construct(private readonly BulkActionResult $result)
    {
    }

    public function __invoke(BulkActionRequest $request): BulkActionResult
    {
        $this->lastRequest = $request;

        return $this->result;
    }
}

/**
 * @internal — pass-through validator, returns all IDs unchanged
 */
final class PassthroughValidator implements BulkOwnershipValidator
{
    public function filterOwned(array $rowIds, array $extraContext): array
    {
        return $rowIds;
    }
}

/**
 * @internal — accepts only odd IDs
 */
final class OddOnlyValidator implements BulkOwnershipValidator
{
    public function filterOwned(array $rowIds, array $extraContext): array
    {
        return array_values(array_filter($rowIds, static fn (int|string $id): bool => ((int) $id) % 2 !== 0));
    }
}

/**
 * @internal — rejects every ID
 */
final class RejectAllValidator implements BulkOwnershipValidator
{
    public function filterOwned(array $rowIds, array $extraContext): array
    {
        return [];
    }
}

/**
 * @internal
 */
final class FakeDataSource implements \Quorae\GridBundle\Contract\GridDataSource
{
    public function fetch(object $filter, \Quorae\GridBundle\Dto\Page $page): \Quorae\GridBundle\Dto\GridResponse
    {
        return new \Quorae\GridBundle\Dto\GridResponse(rows: [], hasNext: false, hasPrev: false, page: 1);
    }
}

/**
 * @internal
 */
final class InMemoryGridRegistryForExecutor extends GridRegistry
{
    /**
     * @param array<string, GridDefinition> $definitions
     */
    public function __construct(private readonly array $definitions)
    {
    }

    public function get(string $name): GridDefinition
    {
        if (!isset($this->definitions[$name])) {
            throw new \RuntimeException(\sprintf('InMemoryGridRegistryForExecutor: no definition for "%s".', $name));
        }

        return $this->definitions[$name];
    }
}
