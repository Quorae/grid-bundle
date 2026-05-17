<?php

declare(strict_types=1);

namespace Quorae\GridBundle\Tests\Handler;

use Quorae\GridBundle\Handler\ScalarCoercer;
use Quorae\GridBundle\Tests\Fixtures\DummyStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests the individual coercion methods of {@see ScalarCoercer}.
 *
 * The top-level `coerce()` dispatch (ReflectionNamedType routing to the
 * correct coercion method) is already covered by {@see FilterHydratorTest}
 * which exercises the full hydration pipeline end-to-end.
 */
final class ScalarCoercerTest extends TestCase
{
    private ScalarCoercer $coercer;

    protected function setUp(): void
    {
        $this->coercer = new ScalarCoercer();
    }

    // --- coerceInt ---

    public function testCoerceIntFromInt(): void
    {
        self::assertSame(42, $this->coercer->coerceInt(42));
    }

    public function testCoerceIntFromNumericString(): void
    {
        self::assertSame(7, $this->coercer->coerceInt('7'));
    }

    public function testCoerceIntReturnsNullForEmptyString(): void
    {
        self::assertNull($this->coercer->coerceInt(''));
    }

    public function testCoerceIntReturnsNullForNonNumericString(): void
    {
        self::assertNull($this->coercer->coerceInt('abc'));
    }

    public function testCoerceIntReturnsNullForArray(): void
    {
        self::assertNull($this->coercer->coerceInt(['x']));
    }

    public function testCoerceIntReturnsNullForFloat(): void
    {
        self::assertNull($this->coercer->coerceInt(3.14));
    }

    // --- coerceString ---

    public function testCoerceStringFromString(): void
    {
        self::assertSame('hello', $this->coercer->coerceString('hello'));
    }

    /**
     * Delta C (port-map §2.C) : SimpleCRM Billing row/filter ids are UUIDv7
     * strings (`Symfony\Component\Uid\Uuid` is `\Stringable`). The framework
     * must preserve such a string **byte-for-byte** through scalar coercion —
     * no normalisation, no truncation — otherwise mixed-PK grids would corrupt
     * Billing identifiers. This pins the invariant the delta relies on.
     */
    public function testCoerceStringPreservesUuidStringVerbatim(): void
    {
        $uuid = '0190b3c4-7e21-7c8a-9f2d-1a2b3c4d5e6f';

        self::assertSame($uuid, $this->coercer->coerceString($uuid));
    }

    public function testCoerceDispatchPreservesUuidStringForStringType(): void
    {
        $uuid = '0190b3c4-7e21-7c8a-9f2d-1a2b3c4d5e6f';

        $type = (new \ReflectionMethod(UuidStringFilterStub::class, '__construct'))
            ->getParameters()[0]
            ->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $type);

        self::assertSame($uuid, $this->coercer->coerce($uuid, $type));
    }

    public function testCoerceStringFromInt(): void
    {
        self::assertSame('42', $this->coercer->coerceString(42));
    }

    public function testCoerceStringFromFloat(): void
    {
        self::assertSame('3.14', $this->coercer->coerceString(3.14));
    }

    public function testCoerceStringReturnsNullForArray(): void
    {
        self::assertNull($this->coercer->coerceString(['x']));
    }

    public function testCoerceStringReturnsNullForBool(): void
    {
        self::assertNull($this->coercer->coerceString(true));
    }

    // --- coerceBool ---

    public function testCoerceBoolFromBoolTrue(): void
    {
        self::assertTrue($this->coercer->coerceBool(true));
    }

    public function testCoerceBoolFromBoolFalse(): void
    {
        self::assertFalse($this->coercer->coerceBool(false));
    }

    public function testCoerceBoolFromTruthyString(): void
    {
        self::assertTrue($this->coercer->coerceBool('1'));
        self::assertTrue($this->coercer->coerceBool('true'));
        self::assertTrue($this->coercer->coerceBool('yes'));
        self::assertTrue($this->coercer->coerceBool('on'));
    }

    public function testCoerceBoolFromFalsyString(): void
    {
        self::assertFalse($this->coercer->coerceBool('0'));
        self::assertFalse($this->coercer->coerceBool('false'));
        self::assertFalse($this->coercer->coerceBool('no'));
        self::assertFalse($this->coercer->coerceBool('off'));
    }

    public function testCoerceBoolReturnsNullForAmbiguousString(): void
    {
        self::assertNull($this->coercer->coerceBool('maybe'));
    }

    public function testCoerceBoolFromNonZeroInt(): void
    {
        self::assertTrue($this->coercer->coerceBool(1));
        self::assertTrue($this->coercer->coerceBool(99));
    }

    public function testCoerceBoolFromZeroInt(): void
    {
        self::assertFalse($this->coercer->coerceBool(0));
    }

    public function testCoerceBoolReturnsNullForArray(): void
    {
        self::assertNull($this->coercer->coerceBool(['x']));
    }

    // --- coerceFloat ---

    public function testCoerceFloatFromFloat(): void
    {
        self::assertSame(3.14, $this->coercer->coerceFloat(3.14));
    }

    public function testCoerceFloatFromInt(): void
    {
        self::assertSame(5.0, $this->coercer->coerceFloat(5));
    }

    public function testCoerceFloatFromNumericString(): void
    {
        self::assertSame(2.5, $this->coercer->coerceFloat('2.5'));
    }

    public function testCoerceFloatReturnsNullForNonNumericString(): void
    {
        self::assertNull($this->coercer->coerceFloat('abc'));
    }

    public function testCoerceFloatReturnsNullForEmptyString(): void
    {
        self::assertNull($this->coercer->coerceFloat(''));
    }

    public function testCoerceFloatReturnsNullForArray(): void
    {
        self::assertNull($this->coercer->coerceFloat(['x']));
    }

    // --- coerceEnum ---

    public function testCoerceEnumFromValidStringValue(): void
    {
        self::assertSame(DummyStatus::Open, $this->coercer->coerceEnum('open', DummyStatus::class));
    }

    public function testCoerceEnumFromEnumInstance(): void
    {
        self::assertSame(DummyStatus::Closed, $this->coercer->coerceEnum(DummyStatus::Closed, DummyStatus::class));
    }

    public function testCoerceEnumReturnsNullForInvalidValue(): void
    {
        self::assertNull($this->coercer->coerceEnum('nonexistent', DummyStatus::class));
    }

    public function testCoerceEnumReturnsNullForArray(): void
    {
        self::assertNull($this->coercer->coerceEnum(['x'], DummyStatus::class));
    }
}

/**
 * @internal — minimal filter DTO whose first constructor parameter is a
 * `string` (a Billing UUID id, delta C). Used to obtain a real
 * {@see \ReflectionNamedType} of `string` for the dispatch test.
 */
final class UuidStringFilterStub
{
    public function __construct(public string $id)
    {
    }
}
