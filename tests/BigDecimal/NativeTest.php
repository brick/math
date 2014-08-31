<?php

namespace Brick\Math\Tests\BigDecimal;

use Brick\Math\Tests\BigDecimalTest;
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
