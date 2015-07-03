<?php

namespace Brick\Math\Internal;

use Brick\Math\RoundingMode;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Performs basic operations on arbitrary size integers.
 *
 * All parameters must be validated as non-empty strings of digits,
 * without leading zero, and with an optional leading minus sign if the number is not zero.
 *
 * Any other parameter format will lead to undefined behaviour.
 * All methods must return strings respecting this format.
 *
 * @internal
 */
abstract class Calculator
{
    /**
     * The maximum exponent value allowed for the pow() method.
     */
    const MAX_POWER = 1000000;

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
     * Extracts the digits, sign, and length of the operands.
     *
     * @param string $a    The first operand.
     * @param string $b    The second operand.
     * @param string $aDig A variable to store the digits of the first operand.
     * @param string $bDig A variable to store the digits of the second operand.
     * @param bool   $aNeg A variable to store whether the first operand is negative.
     * @param bool   $bNeg A variable to store whether the second operand is negative.
     * @param bool   $aLen A variable to store the number of digits in the first operand.
     * @param bool   $bLen A variable to store the number of digits in the second operand.
     *
     * @return void
     */
    final protected function init($a, $b, & $aDig, & $bDig, & $aNeg, & $bNeg, & $aLen, & $bLen)
    {
        $aNeg = ($a[0] === '-');
        $bNeg = ($b[0] === '-');

        $aDig = $aNeg ? substr($a, 1) : $a;
        $bDig = $bNeg ? substr($b, 1) : $b;

        $aLen = strlen($aDig);
        $bLen = strlen($bDig);
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
     * Compares two numbers.
     *
     * @param string $a The first number.
     * @param string $b The second number.
     *
     * @return int [-1, 0, 1] If the first number is less than, equal to, or greater than the second number.
     */
    public function cmp($a, $b)
    {
        $this->init($a, $b, $aDig, $bDig, $aNeg, $bNeg, $aLen, $bLen);

        if ($aNeg && ! $bNeg) {
            return -1;
        }

        if ($bNeg && ! $aNeg) {
            return 1;
        }

        if ($aLen < $bLen) {
            $result = -1;
        } elseif ($aLen > $bLen) {
            $result = 1;
        } else {
            $result = strcmp($aDig, $bDig);

            if ($result < 0) {
                $result = -1;
            } elseif ($result > 0) {
                $result = 1;
            }
        }

        return $aNeg ? -$result : $result;
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
     * Returns the quotient of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string The quotient.
     */
    abstract public function divQ($a, $b);

    /**
     * Returns the remainder of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string The remainder.
     */
    abstract public function divR($a, $b);

    /**
     * Returns the quotient and remainder of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string[] An array containing the quotient and remainder.
     */
    abstract public function divQR($a, $b);

    /**
     * Exponentiates a number.
     *
     * @param string $a The base.
     * @param int    $e The exponent, validated as an integer between 0 and MAX_POWER.
     *
     * @return string The power.
     */
    abstract public function pow($a, $e);

    /**
     * Returns the greatest common divisor of the two numbers.
     *
     * This method can be overridden by the concrete implementation if the underlying library
     * has built-in support for GCD calculations.
     *
     * @param string $a The first number.
     * @param string $b The second number.
     *
     * @return string The GCD, always positive, or zero if both arguments are zero.
     */
    public function gcd($a, $b)
    {
        if ($a === '0') {
            return $this->abs($b);
        }

        if ($b === '0') {
            return $this->abs($a);
        }

        return $this->gcd($b, $this->divR($a, $b));
    }

    /**
     * Performs a rounded division.
     *
     * Rounding is performed when the remainder of the division is not zero.
     *
     * @param string $a            The dividend.
     * @param string $b            The divisor.
     * @param int    $roundingMode The rounding mode.
     *
     * @return string
     *
     * @throws \InvalidArgumentException  If the rounding mode is invalid.
     * @throws RoundingNecessaryException If RoundingMode::UNNECESSARY is provided but rounding is necessary.
     */
    public function divRound($a, $b, $roundingMode)
    {
        list ($quotient, $remainder) = $this->divQR($a, $b);

        $hasDiscardedFraction = ($remainder !== '0');
        $isPositiveOrZero = ($a[0] === '-') === ($b[0] === '-');

        $discardedFractionSign = function() use ($remainder, $b) {
            $r = $this->abs($this->mul($remainder, '2'));
            $b = $this->abs($b);

            return $this->cmp($r, $b);
        };

        $increment = false;

        switch ($roundingMode) {
            case RoundingMode::UNNECESSARY:
                if ($hasDiscardedFraction) {
                    throw RoundingNecessaryException::roundingNecessary();
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
                $lastDigit = (int) substr($quotient, -1);
                $lastDigitIsEven = ($lastDigit % 2 === 0);
                $increment = $lastDigitIsEven ? $discardedFractionSign() > 0 : $discardedFractionSign() >= 0;
                break;

            default:
                throw new \InvalidArgumentException('Invalid rounding mode.');
        }

        if ($increment) {
            return $this->add($quotient, $isPositiveOrZero ? '1' : '-1');
        }

        return $quotient;
    }
}
