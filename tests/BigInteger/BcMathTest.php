<?php

namespace Brick\Math\Tests\BigInteger;

use Brick\Math\Tests\BigIntegerTest;
use Brick\Math\Internal\Calculator\BcMathCalculator;

/**
 * @requires extension bcmath
 */
class BcMathTest extends BigIntegerTest
{
    /**
     * @inheritdoc
     */
    public function getCalculator()
    {
        return new BcMathCalculator();
    }
}
