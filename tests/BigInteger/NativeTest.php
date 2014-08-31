<?php

namespace Brick\Tests\Math\BigInteger;

use Brick\Tests\Math\BigIntegerTest;
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
