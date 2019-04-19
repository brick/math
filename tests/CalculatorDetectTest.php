<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\Internal\Calculator;

use PHPUnit\Framework\TestCase;

/**
 * Tests for Calculator implementation detection.
 */
class CalculatorDetectTest extends TestCase
{
    public function testGetWithNoCalculatorSetDetectsCalculator()
    {
        $currentCalculator = Calculator::get();

        Calculator::set(null);
        $this->assertInstanceOf(Calculator::class, Calculator::get());

        Calculator::set($currentCalculator);
    }
}
