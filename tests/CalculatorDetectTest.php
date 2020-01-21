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
    public function testGetWithNoCalculatorSetDetectsCalculator() : void
    {
        $currentCalculator = Calculator::get();

        Calculator::set(null);
        self::assertInstanceOf(Calculator::class, Calculator::get());

        Calculator::set($currentCalculator);
    }
}
