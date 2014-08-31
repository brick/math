<?php

namespace Brick\Tests\Math\BigDecimal;

use Brick\Tests\Math\BigDecimalTest;
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
