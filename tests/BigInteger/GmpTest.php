<?php

namespace Brick\Math\Tests\BigInteger;

use Brick\Math\Tests\BigIntegerTest;
use Brick\Math\Internal\Calculator\GmpCalculator;

/**
 * @requires extension gmp
 */
class GmpTest extends BigIntegerTest
{
    /**
     * @inheritdoc
     */
    public function getCalculator()
    {
        return new GmpCalculator();
    }
}
