<?php

namespace Brick\Tests\Math\BigDecimal;

use Brick\Tests\Math\BigDecimalTest;
use Brick\Math\Internal\Calculator\NativeCalculator;

/**
 * Runs the BigDecimal tests using the native calculator.
 */
class NativeTest extends BigDecimalTest
{
    /**
     * @inheritdoc
     */
    public function getCalculator()
    {
        return new NativeCalculator();
    }
}
