<?php

namespace Brick\Math\Internal;

use Brick\Math\RoundingMode;

/**
 * Performs basic operations on arbitrary size integers.
 *
 * All parameters must be validated as non-empty strings of digits,
 * without leading zero, and with an optional leading minus sign.
 *
 * Any other parameter format will lead to undefined behaviour.
 * All methods must return strings respecting this format.
 *
 * @internal
 */
abstract class Calculator
{
    /**
     * The Calculator instance in use.
     *
     * @var Calculator|null
     */
    private static $instance = null;

    /**
     * Sets the Calculator instance to use.
     *
     * An instance is typically set only in unit tests: the autodetect is usually the best option.
     *
     * @param Calculator|null $calculator The calculator instance, or NULL to revert to autodetect.
     *
     * @return void
     */
    public static function set(Calculator $calculator = null)
    {
        self::$instance = $calculator;
    }

    /**
     * Returns the Calculator instance to use.
     *
     * If none has been explicitly set, the fastest available implementation will be returned.
     *
     * @return Calculator
     */
    public static function get()
    {
        if (self::$instance === null) {
            self::$instance = self::detect();
        }

        return self::$instance;
    }

    /**
     * Returns the fastest available Calculator implementation.
     *
     * @codeCoverageIgnore
     *
     * @return Calculator
     */
    private static function detect()
    {
        if (extension_loaded('gmp')) {
            return new Calculator\GmpCalculator();
        }

        if (extension_loaded('bcmath')) {
            return new Calculator\BcMathCalculator();
        }

        return new Calculator\NativeCalculator();
    }

    /**
     * Returns the absolute value of a number.
     *
     * @param string $n The number.
     *
     * @return string The absolute value.
     */
    public function abs($n)
    {
        return ($n[0] === '-') ? substr($n, 1) : $n;
    }

    /**
     * Negates a number.
     *
     * @param string $n The number.
     *
     * @return string The negated value.
     */
    public function neg($n)
    {
        if ($n === '0') {
            return '0';
        }

        if ($n[0] === '-') {
            return substr($n, 1);
        }

        return '-' . $n;
    }

    /**
     * Returns an integer representing the sign of the given number.
     *
     * @param string $n
     *
     * @return integer [-1, 0, 1] If the number is negative, zero, or positive.
     */
    public function sign($n)
    {
        if ($n === '0') {
            return 0;
        }

        if ($n[0] === '-') {
            return -1;
        }

        return 1;
    }

    /**
     * Compares two numbers.
     *
     * @param string $a The first number.
     * @param string $b The second number.
     *
     * @return integer [-1, 0, 1] If the first number is less than, equal to, or greater than the second number.
     */
    public function cmp($a, $b)
    {
        return $this->sign($this->sub($a, $b));
    }

    /**
     * Divides and rounds numbers.
     *
     * @param string  $p            The dividend.
     * @param string  $q            The divisor, must not be zero.
     * @param integer $roundingMode The rounding mode.
     *
     * @return string|null The divided and rounded number, or null if rounding was necessary.
     *
     * @throws \InvalidArgumentException If the rounding mode is invalid.
     */
    public function divRounded($p, $q, $roundingMode)
    {
        list ($result, $remainder) = $this->div($p, $q);

        $hasDiscardedFraction = ($remainder !== '0');
        $isPositiveOrZero = ($p[0] === '-') === ($q[0] === '-');

        $discardedFractionSign = function() use ($remainder, $q) {
            $r = $this->abs($this->mul($remainder, '2'));
            $q = $this->abs($q);

            return $this->cmp($r, $q);
        };

        $increment = false;

        switch ($roundingMode) {
            case RoundingMode::UNNECESSARY:
                if ($hasDiscardedFraction) {
                    return null;
                }
                break;

            case RoundingMode::UP:
                $increment = $hasDiscardedFraction;
                break;

            case RoundingMode::DOWN:
                break;

            case RoundingMode::CEILING:
                $increment = $hasDiscardedFraction && $isPositiveOrZero;
                break;

            case RoundingMode::FLOOR:
                $increment = $hasDiscardedFraction && ! $isPositiveOrZero;
                break;

            case RoundingMode::HALF_UP:
                $increment = $discardedFractionSign() >= 0;
                break;

            case RoundingMode::HALF_DOWN:
                $increment = $discardedFractionSign() > 0;
                break;

            case RoundingMode::HALF_CEILING:
                $increment = $isPositiveOrZero ? $discardedFractionSign() >= 0 : $discardedFractionSign() > 0;
                break;

            case RoundingMode::HALF_FLOOR:
                $increment = $isPositiveOrZero ? $discardedFractionSign() > 0 : $discardedFractionSign() >= 0;
                break;

            case RoundingMode::HALF_EVEN:
                $lastDigit = (int) substr($result, -1);
                $lastDigitIsEven = ($lastDigit % 2 === 0);
                $increment = $lastDigitIsEven ? $discardedFractionSign() > 0 : $discardedFractionSign() >= 0;
                break;

            default:
                throw new \InvalidArgumentException('Invalid rounding mode.');
        }

        if ($increment) {
            $result = $this->add($result, $isPositiveOrZero ? '1' : '-1');
        }

        return $result;
    }

    /**
     * Adds two numbers.
     *
     * @param string $a The augend.
     * @param string $b The addend.
     *
     * @return string The sum.
     */
    abstract public function add($a, $b);

    /**
     * Subtracts two numbers.
     *
     * @param string $a The minuend.
     * @param string $b The subtrahend.
     *
     * @return string The difference.
     */
    abstract public function sub($a, $b);

    /**
     * Multiplies two numbers.
     *
     * @param string $a The multiplicand.
     * @param string $b The multiplier.
     *
     * @return string The product.
     */
    abstract public function mul($a, $b);

    /**
     * Divides two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string[] An array containing the quotient and remainder.
     */
    abstract public function div($a, $b);

    /**
     * Exponentiates a number.
     *
     * @param string  $a The base.
     * @param integer $e The exponent, must be a positive-or-zero integer.
     *
     * @return string The power.
     */
    abstract public function pow($a, $e);
}
