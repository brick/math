<?php

declare(strict_types=1);

namespace Brick\Math\Internal;

use function rtrim;
use function strlen;

/**
 * Shared helper for decimal operations.
 *
 * @internal
 */
final class DecimalHelper
{
    private function __construct()
    {
    }

    /**
     * Computes the scale needed to represent the exact decimal result of a reduced fraction.
     *
     * Returns null if the denominator has prime factors other than 2 or 5.
     *
     * @param string $denominator The denominator of the reduced fraction. Must be strictly positive.
     *
     * @pure
     */
    public static function computeScaleFromReducedFractionDenominator(string $denominator): ?int
    {
        $calculator = CalculatorRegistry::get();

        $d = rtrim($denominator, '0');
        $scale = strlen($denominator) - strlen($d);

        foreach ([5, 2] as $prime) {
            for (; ;) {
                $lastDigit = (int) $d[-1];

                if ($lastDigit % $prime !== 0) {
                    break;
                }

                $d = $calculator->divQ($d, (string) $prime);
                $scale++;
            }
        }

        return $d === '1' ? $scale : null;
    }
}
