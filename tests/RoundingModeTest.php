<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\RoundingMode;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

/**
 * Tests for enum RoundingMode.
 */
class RoundingModeTest extends TestCase
{
    #[RequiresPhp('>= 8.4')]
    public function testFromNativeRoundingMode(): void
    {
        foreach (\RoundingMode::cases() as $nativeRoundingMode) {
            $expected = match ($nativeRoundingMode) {
                \RoundingMode::AwayFromZero => RoundingMode::Up,
                \RoundingMode::TowardsZero => RoundingMode::Down,
                \RoundingMode::PositiveInfinity => RoundingMode::Ceiling,
                \RoundingMode::NegativeInfinity => RoundingMode::Floor,
                \RoundingMode::HalfAwayFromZero => RoundingMode::HalfUp,
                \RoundingMode::HalfTowardsZero => RoundingMode::HalfDown,
                \RoundingMode::HalfEven => RoundingMode::HalfEven,
                \RoundingMode::HalfOdd => RoundingMode::HalfOdd,
            };

            self::assertSame($expected, RoundingMode::fromNativeRoundingMode($nativeRoundingMode));
        }
    }
}
