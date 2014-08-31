<?php

namespace Brick\Tests\Math;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Internal\Calculator;

/**
 * Base class for BigInteger and BigDecimal test cases.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Brick\Math\Internal\Calculator
     */
    abstract public function getCalculator();

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        Calculator::set($this->getCalculator());
    }

    /**
     * @param string     $expected The expected value as a string.
     * @param BigInteger $actual   The BigInteger instance to test.
     */
    protected function assertBigIntegerEquals($expected, BigInteger $actual)
    {
        $this->assertSame($expected, (string) BigInteger::of($actual));
    }

    /**
     * @param string     $unscaledValue The expected unscaled value.
     * @param integer    $scale         The expected scale.
     * @param BigDecimal $actual        The BigDecimal instance to test.
     */
    protected function assertBigDecimalEquals($unscaledValue, $scale, BigDecimal $actual)
    {
        $this->assertSame($unscaledValue, $actual->getUnscaledValue());
        $this->assertSame($scale, $actual->getScale());
    }
}
