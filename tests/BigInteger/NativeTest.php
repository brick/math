<?php

namespace Brick\Math\Tests\BigInteger;

use Brick\Math\Tests\BigIntegerTest;
use Brick\Math\Internal\Calculator\NativeCalculator;

/**
 * Runs the BigInteger tests using the native calculator.
 */
class NativeTest extends BigIntegerTest
{
    /**
     * @inheritdoc
     */
    public function getCalculator()
    {
        return new NativeCalculator();
    }
}
