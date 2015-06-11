<?php

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;

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
}
