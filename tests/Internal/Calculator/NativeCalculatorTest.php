<?php

namespace Brick\Math\Tests\Internal\Calculator;

use Brick\Math\Internal\Calculator\NativeCalculator;
use Brick\Math\Tests\AbstractTestCase;

/**
 * Unit tests for class NativeCalculator.
 */
class NativeCalculatorTest extends AbstractTestCase
{
    /**
     * @dataProvider providerAdd
     *
     * @param string $a
     * @param string $b
     * @param string $expectedValue
     */
    public function testAdd($a, $b, $expectedValue)
    {
        $nativeCalculator = new NativeCalculator();
        $this->assertSame($expectedValue, $nativeCalculator->add($a, $b));
    }

    /**
     * @return array
     */
    public function providerAdd()
    {
        return [
            ['0', '1234567891234567889999999', '1234567891234567889999999'],
            ['1234567891234567889999999', '0', '1234567891234567889999999'],

            ['1234567891234567889999999', '-1234567891234567889999999', '0'],
            ['-1234567891234567889999999', '1234567891234567889999999', '0'],

            ['1234567891234567889999999', '1234567891234567889999999', '2469135782469135779999998'],
        ];
    }

    /**
     * @dataProvider providerMul
     *
     * @param string $a
     * @param string $b
     * @param string $expectedValue   
     */
    public function testMul($a, $b, $expectedValue)
    {
        $nativeCalculator = new NativeCalculator();
        $this->assertSame($expectedValue, $nativeCalculator->mul($a, $b));
    }

    /**
     * @return array
     */
    public function providerMul()
    {
        return [
            ['0', '0', '0'],

            ['1', '1234567891234567889999999', '1234567891234567889999999'],
            ['1234567891234567889999999', '1', '1234567891234567889999999'],

            ['1234567891234567889999999', '-1234567891234567889999999', '-1524157878067367851562259605883269630864220000001'],
            ['-1234567891234567889999999', '1234567891234567889999999', '-1524157878067367851562259605883269630864220000001'],

            ['1234567891234567889999999', '1234567891234567889999999', '1524157878067367851562259605883269630864220000001'],
        ];
    }

    /**
     * @dataProvider providerPow
     *
     * @param string $a
     * @param string $b
     * @param string $expectedValue
     */
    public function testPow($a, $b, $expectedValue)
    {
        $nativeCalculator = new NativeCalculator();
        $this->assertSame($expectedValue, $nativeCalculator->pow($a, $b));
    }

    /**
     * @return array
     */
    public function providerPow()
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
