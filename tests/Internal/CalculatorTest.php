<?php

declare(strict_types=1);

namespace Brick\Math\Tests\Internal;

use Brick\Math\Internal\CalculatorRegistry;
use Brick\Math\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the edge cases of the Calculator contract that the public API handles itself,
 * and therefore never delegates to the calculator.
 */
final class CalculatorTest extends AbstractTestCase
{
    #[DataProvider('providerPowWithZeroExponent')]
    public function testPowWithZeroExponent(string $a): void
    {
        self::assertSame('1', CalculatorRegistry::get()->pow($a, 0));
    }

    public static function providerPowWithZeroExponent(): array
    {
        return [
            ['0'],
            ['1'],
            ['-1'],
            ['2'],
            ['123456789012345678901234567890'],
            ['-123456789012345678901234567890'],
        ];
    }

    #[DataProvider('providerModInverseWithModulusOne')]
    public function testModInverseWithModulusOne(string $x): void
    {
        self::assertSame('0', CalculatorRegistry::get()->modInverse($x, '1'));
    }

    public static function providerModInverseWithModulusOne(): array
    {
        return [
            ['0'],
            ['1'],
            ['-1'],
            ['2'],
            ['123456789012345678901234567890'],
            ['-123456789012345678901234567890'],
        ];
    }

    #[DataProvider('providerLcmWithZero')]
    public function testLcmWithZero(string $a, string $b): void
    {
        self::assertSame('0', CalculatorRegistry::get()->lcm($a, $b));
    }

    public static function providerLcmWithZero(): array
    {
        return [
            ['0', '0'],
            ['0', '1'],
            ['1', '0'],
            ['0', '-1'],
            ['-1', '0'],
            ['0', '123456789012345678901234567890'],
            ['123456789012345678901234567890', '0'],
        ];
    }
}
