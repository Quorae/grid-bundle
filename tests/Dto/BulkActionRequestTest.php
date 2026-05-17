<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Dto;

use Quorae\GridBundle\Contract\BulkActor;
use Quorae\GridBundle\Dto\BulkActionRequest;
use PHPUnit\Framework\TestCase;

/**
 * Pins delta B (port-map §2.B) : {@see BulkActionRequest::$actor} is typed
 * against the bundle-local {@see BulkActor} marker — never a host user
 * entity — so the bundle stays standalone.
 */
final class BulkActionRequestTest extends TestCase
{
    public function testStoresAllFields(): void
    {
        $actor = new FakeBulkActor();

        $request = new BulkActionRequest(
            gridName: 'memos',
            actionName: 'delete',
            rowIds: [1, 2, 3],
            extraContext: ['dossier' => 'opaque'],
            actor: $actor,
        );

        self::assertSame('memos', $request->gridName);
        self::assertSame('delete', $request->actionName);
        self::assertSame([1, 2, 3], $request->rowIds);
        self::assertSame(['dossier' => 'opaque'], $request->extraContext);
        self::assertSame($actor, $request->actor);
    }

    public function testActorIsTypedAgainstBulkActorMarker(): void
    {
        $type = (new \ReflectionProperty(BulkActionRequest::class, 'actor'))->getType();

        self::assertInstanceOf(\ReflectionNamedType::class, $type);
        self::assertSame(BulkActor::class, $type->getName());
    }

    /**
     * Delta C (port-map §2.C) : row ids stay `int|string` — Billing UUID
     * ids flow through as strings without coercion.
     */
    public function testAcceptsStringRowIds(): void
    {
        $request = new BulkActionRequest(
            gridName: 'audit',
            actionName: 'archive',
            rowIds: ['0190b3c4-7e21-7c8a-9f2d-1a2b3c4d5e6f', 'b'],
            extraContext: [],
            actor: new FakeBulkActor(),
        );

        self::assertSame(['0190b3c4-7e21-7c8a-9f2d-1a2b3c4d5e6f', 'b'], $request->rowIds);
    }
}

/**
 * @internal — {@see BulkActor} stand-in for the dormant bulk subsystem.
 */
final class FakeBulkActor implements BulkActor
{
}
