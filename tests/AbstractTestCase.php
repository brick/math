<?php

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/**
 * Base class for BigInteger and BigDecimal test cases.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string     $expected The expected value as a string.
     * @param BigInteger $actual   The BigInteger instance to test.
     */
    protected function assertBigIntegerEquals($expected, BigInteger $actual)
    {
        $this->assertSame($expected, (string) $actual);
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

    /**
     * @param string      $numerator
     * @param string      $denominator
     * @param BigRational $actual
     */
    protected function assertBigRationalEquals($numerator, $denominator, BigRational $actual)
    {
        $this->assertSame($numerator, (string) $actual->getNumerator());
        $this->assertSame($denominator, (string) $actual->getDenominator());
    }
}
