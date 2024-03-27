<?php

declare(strict_types=1);

namespace Brick\Math\Tests\Internal\Calculator;

use Brick\Math\Internal\Calculator\NativeCalculator;
use Brick\Math\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class NativeCalculator.
 */
class NativeCalculatorTest extends AbstractTestCase
{
    #[DataProvider('providerAdd')]
    public function testAdd(string $a, string $b, string $expectedValue) : void
    {
        $nativeCalculator = new NativeCalculator();
        self::assertSame($expectedValue, $nativeCalculator->add($a, $b));
    }

    public static function providerAdd() : array
    {
        return [
            ['0', '1234567891234567889999999', '1234567891234567889999999'],
            ['1234567891234567889999999', '0', '1234567891234567889999999'],

            ['1234567891234567889999999', '-1234567891234567889999999', '0'],
            ['-1234567891234567889999999', '1234567891234567889999999', '0'],

            ['1234567891234567889999999', '1234567891234567889999999', '2469135782469135779999998'],
        ];
    }

    #[DataProvider('providerMul')]
    public function testMul(string $a, string $b, string $expectedValue) : void
    {
        $nativeCalculator = new NativeCalculator();
        self::assertSame($expectedValue, $nativeCalculator->mul($a, $b));
    }

    public static function providerMul() : array
    {
        return [
            ['0', '0', '0'],

            ['0', '1234567891234567889999999', '0'],
            ['1234567891234567889999999', '0', '0'],

            ['1', '1234567891234567889999999', '1234567891234567889999999'],
            ['1234567891234567889999999', '1', '1234567891234567889999999'],

            ['1234567891234567889999999', '-1234567891234567889999999', '-1524157878067367851562259605883269630864220000001'],
            ['-1234567891234567889999999', '1234567891234567889999999', '-1524157878067367851562259605883269630864220000001'],

            ['1234567891234567889999999', '1234567891234567889999999', '1524157878067367851562259605883269630864220000001'],
        ];
    }

    #[DataProvider('providerPow')]
    public function testPow(string $a, int $b, string $expectedValue) : void
    {
        $nativeCalculator = new NativeCalculator();
        self::assertSame($expectedValue, $nativeCalculator->pow($a, $b));
    }

    public static function providerPow() : array
    {
        return [
            ['123456789012345678901234567890', 0, '1'],

            ['1', 2, '1'],
            ['1234567891234567889999999', 1, '1234567891234567889999999'],

            ['1234567891234567889999999', -2, '1'],
            ['-1234567891234567889999999', 2, '1524157878067367851562259605883269630864220000001'],

            ['1234567891234567889999999', 3, '1881676377434183981909558127466713752376807174114547646517403703669999999'],
        ];
    }
}
