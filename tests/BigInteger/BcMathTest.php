<?php

namespace Brick\Tests\Math\BigInteger;

use Brick\Tests\Math\BigIntegerTest;
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
