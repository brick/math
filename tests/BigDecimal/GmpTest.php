<?php

namespace Brick\Math\Tests\BigDecimal;

use Brick\Math\Tests\BigDecimalTest;
use Brick\Math\Internal\Calculator\GmpCalculator;

/**
 * @requires extension gmp
 */
class GmpTest extends BigDecimalTest
{
    /**
     * @inheritdoc
     */
    public function getCalculator()
    {
        return new GmpCalculator();
    }
}
