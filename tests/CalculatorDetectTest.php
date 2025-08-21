<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\Internal\Calculator;

use Brick\Math\Internal\CalculatorRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Calculator implementation detection.
 */
class CalculatorDetectTest extends TestCase
{
    public function testGetWithNoCalculatorSetDetectsCalculator() : void
    {
        $currentCalculator = CalculatorRegistry::get();

        CalculatorRegistry::set(null);
        self::assertInstanceOf(Calculator::class, CalculatorRegistry::get());

        CalculatorRegistry::set($currentCalculator);
    }
}
