<?php

namespace Brick\Math\Tests\BigDecimal;

use Brick\Math\Tests\BigDecimalTest;
use Brick\Math\Internal\Calculator\BcMathCalculator;

/**
 * @requires extension bcmath
 */
class BcMathTest extends BigDecimalTest
{
    /**
     * @inheritdoc
     */
    public function getCalculator()
    {
        return new BcMathCalculator();
    }
}
