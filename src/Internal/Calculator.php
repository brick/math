<?php

declare(strict_types=1);

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
 * All methods must return strings respecting this format, unless specified otherwise.
 *
 * @internal
 */
abstract class Calculator
{
    /**
     * The maximum exponent value allowed for the pow() method.
     */
    public const MAX_POWER = 1000000;

    /**
     * The dictionary for reading and writing in base 2 to 36.
     */
    public const DICTIONARY = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * The Calculator instance in use.
     *
     * @var Calculator|null
     */
    private static $instance;

    /**
     * Sets the Calculator instance to use.
     *
     * An instance is typically set only in unit tests: the autodetect is usually the best option.
     *
     * @param Calculator|null $calculator The calculator instance, or NULL to revert to autodetect.
     *
     * @return void
     */
    public static function set(?Calculator $calculator) : void
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
    public static function get() : Calculator
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
    private static function detect() : Calculator
    {
        if (\extension_loaded('gmp')) {
            return new Calculator\GmpCalculator();
        }

        if (\extension_loaded('bcmath')) {
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
    final protected function init(string $a, string $b, & $aDig, & $bDig, & $aNeg, & $bNeg, & $aLen, & $bLen) : void
    {
        $aNeg = ($a[0] === '-');
        $bNeg = ($b[0] === '-');

        $aDig = $aNeg ? \substr($a, 1) : $a;
        $bDig = $bNeg ? \substr($b, 1) : $b;

        $aLen = \strlen($aDig);
        $bLen = \strlen($bDig);
    }

    /**
     * Returns the absolute value of a number.
     *
     * @param string $n The number.
     *
     * @return string The absolute value.
     */
    public function abs(string $n) : string
    {
        return ($n[0] === '-') ? \substr($n, 1) : $n;
    }

    /**
     * Negates a number.
     *
     * @param string $n The number.
     *
     * @return string The negated value.
     */
    public function neg(string $n) : string
    {
        if ($n === '0') {
            return '0';
        }

        if ($n[0] === '-') {
            return \substr($n, 1);
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
    public function cmp(string $a, string $b) : int
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
            $result = $aDig <=> $bDig;
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
    abstract public function add(string $a, string $b) : string;

    /**
     * Subtracts two numbers.
     *
     * @param string $a The minuend.
     * @param string $b The subtrahend.
     *
     * @return string The difference.
     */
    abstract public function sub(string $a, string $b) : string;

    /**
     * Multiplies two numbers.
     *
     * @param string $a The multiplicand.
     * @param string $b The multiplier.
     *
     * @return string The product.
     */
    abstract public function mul(string $a, string $b) : string;

    /**
     * Returns the quotient of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string The quotient.
     */
    abstract public function divQ(string $a, string $b) : string;

    /**
     * Returns the remainder of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string The remainder.
     */
    abstract public function divR(string $a, string $b) : string;

    /**
     * Returns the quotient and remainder of the division of two numbers.
     *
     * @param string $a The dividend.
     * @param string $b The divisor, must not be zero.
     *
     * @return string[] An array containing the quotient and remainder.
     */
    abstract public function divQR(string $a, string $b) : array;

    /**
     * Exponentiates a number.
     *
     * @param string $a The base.
     * @param int    $e The exponent, validated as an integer between 0 and MAX_POWER.
     *
     * @return string The power.
     */
    abstract public function pow(string $a, int $e) : string;

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
    public function gcd(string $a, string $b) : string
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
     * Returns the square root of the given number, rounded down.
     *
     * The result is the largest x such that x² ≤ n.
     * The input MUST NOT be negative.
     *
     * @param string $n The number.
     *
     * @return string The square root.
     */
    abstract public function sqrt(string $n) : string;

    /**
     * Converts a number from an arbitrary base.
     *
     * This method can be overridden by the concrete implementation if the underlying library
     * has built-in support for base conversion.
     *
     * @param string $number The number, positive or zero, non-empty, case-insensitively validated for the given base.
     * @param int    $base   The base of the number, validated from 2 to 36.
     *
     * @return string The converted number, following the Calculator conventions.
     */
    public function fromBase(string $number, int $base) : string
    {
        $number = \strtolower($number);

        $result = '0';
        $power = '1';

        for ($i = \strlen($number) - 1; $i >= 0; $i--) {
            $index = \strpos(self::DICTIONARY, $number[$i]);

            if ($index !== 0) {
                $result = $this->add($result, ($index === 1)
                    ? $power
                    : $this->mul($power, (string) $index)
                );
            }

            if ($i !== 0) {
                $power = $this->mul($power, (string) $base);
            }
        }

        return $result;
    }

    /**
     * Converts a number to an arbitrary base.
     *
     * This method can be overridden by the concrete implementation if the underlying library
     * has built-in support for base conversion.
     *
     * @param string $number The number to convert, following the Calculator conventions.
     * @param int    $base   The base to convert to, validated from 2 to 36.
     *
     * @return string The converted number, lowercase.
     */
    public function toBase(string $number, int $base) : string
    {
        $value = $number;
        $negative = ($value[0] === '-');

        if ($negative) {
            $value = \substr($value, 1);
        }

        $base = (string) $base;
        $result = '';

        while ($value !== '0') {
            [$value, $remainder] = $this->divQR($value, $base);
            $remainder = (int) $remainder;

            $result .= self::DICTIONARY[$remainder];
        }

        if ($result === '') {
            return '0';
        }

        if ($negative) {
            $result .= '-';
        }

        return \strrev($result);
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
    public function divRound(string $a, string $b, int $roundingMode) : string
    {
        [$quotient, $remainder] = $this->divQR($a, $b);

        $hasDiscardedFraction = ($remainder !== '0');
        $isPositiveOrZero = ($a[0] === '-') === ($b[0] === '-');

        $discardedFractionSign = function() use ($remainder, $b) : int {
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
                $lastDigit = (int) $quotient[-1];
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

    /**
     * Calculates bitwise AND of two numbers.
     *
     * This method can be overridden by the concrete implementation if the underlying library
     * has built-in support for bitwise operations.
     *
     * @param string $a
     * @param string $b
     *
     * @return string
     */
    public function and(string $a, string $b) : string
    {
        return $this->bitwise('and', $a, $b);
    }

    /**
     * Calculates bitwise OR of two numbers.
     *
     * This method can be overridden by the concrete implementation if the underlying library
     * has built-in support for bitwise operations.
     *
     * @param string $a
     * @param string $b
     *
     * @return string
     */
    public function or(string $a, string $b) : string
    {
        return $this->bitwise('or', $a, $b);
    }

    /**
     * Calculates bitwise XOR of two numbers.
     *
     * This method can be overridden by the concrete implementation if the underlying library
     * has built-in support for bitwise operations.
     *
     * @param string $a
     * @param string $b
     *
     * @return string
     */
    public function xor(string $a, string $b) : string
    {
        return $this->bitwise('xor', $a, $b);
    }

    /**
     * Performs a bitwise operation on a decimal number.
     *
     * @param string $operator The operator to use, must be "and", "or" or "xor".
     * @param string $a        The left operand.
     * @param string $b        The right operand.
     *
     * @return string
     */
    private function bitwise(string $operator, string $a, string $b) : string
    {
        $this->init($a, $b, $aDig, $bDig, $aNeg, $bNeg, $aLen, $bLen);

        $aBin = $this->toBinary($aDig);
        $bBin = $this->toBinary($bDig);

        $aLen = \strlen($aBin);
        $bLen = \strlen($bBin);

        if ($aLen > $bLen) {
            $bBin = \str_repeat("\x00", $aLen - $bLen) . $bBin;
        } elseif ($bLen > $aLen) {
            $aBin = \str_repeat("\x00", $bLen - $aLen) . $aBin;
        }

        if ($aNeg) {
            $aBin = $this->twosComplement($aBin);
        }
        if ($bNeg) {
            $bBin = $this->twosComplement($bBin);
        }

        switch ($operator) {
            case 'and':
                $value = $aBin & $bBin;
                $negative = ($aNeg and $bNeg);
                break;

            case 'or':
                $value = $aBin | $bBin;
                $negative = ($aNeg or $bNeg);
                break;

            case 'xor':
                $value = $aBin ^ $bBin;
                $negative = ($aNeg xor $bNeg);
                break;

            default:
                throw new \InvalidArgumentException('Invalid bitwise operator.');
        }

        if ($negative) {
            $value = $this->twosComplement($value);
        }

        $result = $this->toDecimal($value);

        return $negative ? $this->neg($result) : $result;
    }

    /**
     * @param string $number A positive, binary number.
     *
     * @return string
     */
    private function twosComplement(string $number) : string
    {
        $xor = \str_repeat("\xff", \strlen($number));

        $number = $number ^ $xor;

        for ($i = \strlen($number) - 1; $i >= 0; $i--) {
            $byte = \ord($number[$i]);

            if (++$byte !== 256) {
                $number[$i] = \chr($byte);
                break;
            }

            $number[$i] = \chr(0);
        }

        return $number;
    }

    /**
     * Converts a decimal number to a binary string.
     *
     * @param string $number The number to convert, positive or zero, only digits.
     *
     * @return string
     */
    private function toBinary(string $number) : string
    {
        $calculator = Calculator::get();

        $result = '';

        while ($number !== '0') {
            [$number, $remainder] = $calculator->divQR($number, '256');
            $result .= \chr((int) $remainder);
        }

        return \strrev($result);
    }

    /**
     * Returns the positive decimal representation of a binary number.
     *
     * @param string $bytes The bytes representing the number.
     *
     * @return string
     */
    private function toDecimal(string $bytes) : string
    {
        $calculator = Calculator::get();

        $result = '0';
        $power = '1';

        for ($i = \strlen($bytes) - 1; $i >= 0; $i--) {
            $index = \ord($bytes[$i]);

            if ($index !== 0) {
                $result = $calculator->add($result, ($index === 1)
                    ? $power
                    : $calculator->mul($power, (string) $index)
                );
            }

            if ($i !== 0) {
                $power = $calculator->mul($power, '256');
            }
        }

        return $result;
    }
}
