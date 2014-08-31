<?php

namespace Brick\Tests\Math\BigDecimal;

use Brick\Tests\Math\BigDecimalTest;
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
