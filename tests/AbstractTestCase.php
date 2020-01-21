<?php

declare(strict_types=1);

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
    final protected static function assertBigIntegerEquals(string $expected, BigInteger $actual) : void
    {
        self::assertSame($expected, (string) $actual);
    }

    /**
     * @param string     $expected The expected string representation.
     * @param BigDecimal $actual   The BigDecimal instance to test.
     *
     * @return void
     */
    final protected static function assertBigDecimalEquals(string $expected, BigDecimal $actual) : void
    {
        self::assertSame($expected, (string) $actual);
    }

    /**
     * @param string      $expected The expected string representation.
     * @param BigRational $actual   The BigRational instance to test.
     *
     * @return void
     */
    final protected static function assertBigRationalEquals(string $expected, BigRational $actual) : void
    {
        self::assertSame($expected, (string) $actual);
    }

    /**
     * @param string     $unscaledValue The expected unscaled value, as a string.
     * @param int        $scale         The expected scale.
     * @param BigDecimal $actual        The BigDecimal instance to test.
     *
     * @return void
     */
    final protected static function assertBigDecimalInternalValues(string $unscaledValue, int $scale, BigDecimal $actual) : void
    {
        self::assertSame($unscaledValue, (string) $actual->getUnscaledValue());
        self::assertSame($scale, $actual->getScale());
    }

    /**
     * @param string      $numerator   The expected numerator, as a string.
     * @param string      $denominator The expected denominator, as a string.
     * @param BigRational $actual      The BigRational instance to test.
     *
     * @return void
     */
    final protected static function assertBigRationalInternalValues(string $numerator, string $denominator, BigRational $actual) : void
    {
        self::assertSame($numerator, (string) $actual->getNumerator());
        self::assertSame($denominator, (string) $actual->getDenominator());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    final protected static function isException(string $name) : bool
    {
        return \substr($name, -9) === 'Exception';
    }
}
