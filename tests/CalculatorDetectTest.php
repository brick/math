<?php

namespace Brick\Tests\Math;

use Brick\Math\Internal\Calculator;

/**
 * Tests for Calculator implementation detection.
 */
class CalculatorDetectTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWithNoCalculatorSetDetectsCalculator()
    {
        Calculator::set(null);
        $this->assertInstanceOf(Calculator::class, Calculator::get());
    }
}
