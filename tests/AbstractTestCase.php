<?php

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/**
 * Base class for math tests.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string     $expected The expected value, as a string.
     * @param BigInteger $actual   The BigInteger instance to test.
     */
    final protected function assertBigIntegerEquals($expected, $actual)
    {
        $this->assertInstanceOf(BigInteger::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string     $expected The expected string representation.
     * @param BigDecimal $actual   The BigDecimal instance to test.
     */
    final protected function assertBigDecimalEquals($expected, $actual)
    {
        $this->assertInstanceOf(BigDecimal::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string      $expected The expected string representation.
     * @param BigRational $actual   The BigRational instance to test.
     */
    final protected function assertBigRationalEquals($expected, $actual)
    {
        $this->assertInstanceOf(BigRational::class, $actual);
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string     $unscaledValue The expected unscaled value, as a string.
     * @param int        $scale         The expected scale.
     * @param BigDecimal $actual        The BigDecimal instance to test.
     */
    final protected function assertBigDecimalInternalValues($unscaledValue, $scale, $actual)
    {
        $this->assertInstanceOf(BigDecimal::class, $actual);
        $this->assertSame($unscaledValue, $actual->unscaledValue());
        $this->assertSame($scale, $actual->scale());
    }

    /**
     * @param string      $numerator   The expected numerator, as a string.
     * @param string      $denominator The expected denominator, as a string.
     * @param BigRational $actual      The BigRational instance to test.
     */
    final protected function assertBigRationalInternalValues($numerator, $denominator, $actual)
    {
        $this->assertInstanceOf(BigRational::class, $actual);
        $this->assertSame($numerator, (string) $actual->numerator());
        $this->assertSame($denominator, (string) $actual->denominator());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final protected function isException($name)
    {
        return substr($name, -9) === 'Exception';
    }
}
