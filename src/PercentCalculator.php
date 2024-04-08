<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\MathException;
use DivisionByZeroError;

/**
 * Class PercentCalculator
 *
 * This class defines constants for various types of percentages used in calculations.
 * These constants can be used to specify the type of percentage calculation to be performed.
 */
class PercentCalculator
{
    /**
     * Represents an increase percentage.
     */
    const INCREASE = 'increase';

    /**
     * Represents a decrease percentage.
     */
    const DECREASE = 'decrease';

    /**
     * Represents a total percentage.
     */
    const TOTAL_PERCENTAGE = 'total_percentage';

    /**
     * Represents a reference percentage.
     */
    const REFERENCE_PERCENTAGE = 'reference_percentage';

    /**
     * Represents an average percentage.
     */
    const AVERAGE_PERCENTAGE = 'average_percentage';

    /**
     * Represents a margin percentage.
     */
    const MARGIN_PERCENTAGE = 'margin_percentage';

    /**
     * Represents an interest percentage.
     */
    const INTEREST_PERCENTAGE = 'interest_percentage';

    /**
     * Represents a participation percentage.
     */
    const PARTICIPATION_PERCENTAGE = 'participation_percentage';

    /**
     * Calculates the percentage based on the given type and numbers.
     *
     * @param string $type The type of percentage calculation to perform.
     * @param mixed ...$numbers The numbers to use for the calculation.
     * @return float The calculated percentage.
     */
    public static function calculatePercentage($type, ...$numbers)
    {
        try {
            switch ($type) {
                case self::INCREASE:
                    return self::calculateIncrease(...$numbers);
                    break;
                case self::DECREASE:
                    return self::calculateDecrease(...$numbers);
                    break;
                case self::TOTAL_PERCENTAGE:
                    return self::calculateTotalPercentage(...$numbers);
                    break;
                case self::REFERENCE_PERCENTAGE:
                    return self::calculateReferencePercentage(...$numbers);
                    break;
                case self::AVERAGE_PERCENTAGE:
                    return self::calculateAveragePercentage(...$numbers);
                    break;
                case self::MARGIN_PERCENTAGE:
                    return self::calculateMarginPercentage(...$numbers);
                    break;
                case self::INTEREST_PERCENTAGE:
                    return self::calculateInterestPercentage(...$numbers);
                    break;
                case self::PARTICIPATION_PERCENTAGE:
                    return self::calculateParticipationPercentage(...$numbers);
                    break;
                default:
                    throw new MathException("Invalid percentage calculation type '$type'. Only the following types are supported: increase, decrease, total_percentage, reference_percentage, average_percentage, margin_percentage, interest_percentage, participation_percentage.");
            }
        } catch (DivisionByZeroError) {
            return 0;
        }

        
    }

    /**
     * Calculates the percentage increase between two values.
     *
     * @param float $startValue The starting value.
     * @param float $endValue The ending value.
     * @return float The percentage increase.
     */
    private static function calculateIncrease($startValue, $endValue): float
    {
        return (($endValue - $startValue) / $startValue) * 100;
    }

    /**
     * Calculates the percentage decrease between two values.
     *
     * @param float $startValue The starting value.
     * @param float $endValue The ending value.
     * @return float The percentage decrease.
     */
    private static function calculateDecrease($startValue, $endValue): float
    {
        return (($startValue - $endValue) / $startValue) * 100;
    }

    /**
     * Calculates the percentage of a part relative to a total.
     *
     * @param float $part The part value.
     * @param float $total The total value.
     * @return float The percentage of the part.
     */
    private static function calculateTotalPercentage($part, $total): float
    {
        return ($part / $total) * 100;
    }

    /**
     * Calculates the percentage of a value relative to a reference value.
     *
     * @param float $value The value.
     * @param float $reference The reference value.
     * @return float The percentage of the value.
     */
    private static function calculateReferencePercentage($value, $reference): float
    {
        return ($value / $reference) * 100;
    }

    /**
     * Calculates the average percentage of a list of numbers.
     *
     * @param float ...$numbers The numbers.
     * @return float The average percentage.
     */
    private static function calculateAveragePercentage(...$numbers): float
    {
        $total = array_sum($numbers);
        $count = count($numbers);
        return ($total / $count) * 100;
    }

    /**
     * Calculates the margin percentage between a cost price and a selling price.
     *
     * @param float $costPrice The cost price.
     * @param float $sellingPrice The selling price.
     * @return float The margin percentage.
     */
    private static function calculateMarginPercentage($firstNumber, $secondNumber): float
    {
        return (($secondNumber - $firstNumber) / abs($firstNumber)) * 100;
    }

    /**
     * Calculates the interest amount based on a principal and an interest rate.
     *
     * @param float $principal The principal amount.
     * @param float $interestRate The interest rate.
     * @return float The interest amount.
     */
    private static function calculateInterestPercentage($principal, $interestRate): float
    {
        return $principal * ($interestRate / 100);
    }

    /**
     * Calculates the percentage of own shares relative to total shares.
     *
     * @param float $ownShares The number of own shares.
     * @param float $totalShares The total number of shares.
     * @return float The percentage of own shares.
     */
    private static function calculateParticipationPercentage($ownShares, $totalShares): float
    {
        return ($ownShares / $totalShares) * 100;
    }
}
