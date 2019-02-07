<?php

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;

use PHPUnit\Framework\TestCase;

/**
 * Base class for math tests.
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * @param string     $expected The expected value, as a string.
     * @param BigInteger $actual   The BigInteger instance to test.
     *
     * @return void
     */
    final protected function assertBigIntegerEquals(string $expected, BigInteger $actual) : void
    {
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string     $expected The expected string representation.
     * @param BigDecimal $actual   The BigDecimal instance to test.
     *
     * @return void
     */
    final protected function assertBigDecimalEquals(string $expected, BigDecimal $actual) : void
    {
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string      $expected The expected string representation.
     * @param BigRational $actual   The BigRational instance to test.
     *
     * @return void
     */
    final protected function assertBigRationalEquals(string $expected, BigRational $actual) : void
    {
        $this->assertSame($expected, (string) $actual);
    }

    /**
     * @param string     $unscaledValue The expected unscaled value, as a string.
     * @param int        $scale         The expected scale.
     * @param BigDecimal $actual        The BigDecimal instance to test.
     *
     * @return void
     */
    final protected function assertBigDecimalInternalValues(string $unscaledValue, int $scale, BigDecimal $actual) : void
    {
        $this->assertSame($unscaledValue, (string) $actual->getUnscaledValue());
        $this->assertSame($scale, $actual->getScale());
    }

    /**
     * @param string      $numerator   The expected numerator, as a string.
     * @param string      $denominator The expected denominator, as a string.
     * @param BigRational $actual      The BigRational instance to test.
     *
     * @return void
     */
    final protected function assertBigRationalInternalValues(string $numerator, string $denominator, BigRational $actual) : void
    {
        $this->assertSame($numerator, (string) $actual->getNumerator());
        $this->assertSame($denominator, (string) $actual->getDenominator());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final protected function isException(string $name) : bool
    {
        return \substr($name, -9) === 'Exception';
    }
}
