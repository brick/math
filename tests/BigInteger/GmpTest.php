<?php

namespace Brick\Tests\Math\BigInteger;

use Brick\Tests\Math\BigIntegerTest;
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
