<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\IntegerOverflowException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Internal\Calculator;

/**
 * An arbitrary-size integer.
 *
 * All methods accepting a number as a parameter accept either a BigInteger instance,
 * an integer, or a string representing an arbitrary size integer.
 */
final class BigInteger extends BigNumber
{
    /**
     * The value, as a string of digits with optional leading minus sign.
     *
     * No leading zeros must be present.
     * No leading minus sign must be present if the number is zero.
     *
     * @var string
     */
    private $value;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param string $value A string of digits, with optional leading minus sign.
     */
    protected function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Creates a BigInteger of the given value.
     *
     * @param BigNumber|number|string $value
     *
     * @return BigInteger
     *
     * @throws MathException If the value cannot be converted to a BigInteger.
     */
    public static function of($value) : BigNumber
    {
        return parent::of($value)->toBigInteger();
    }

    /**
     * Creates a number from a string in a given base.
     *
     * The string can optionally be prefixed with the `+` or `-` sign.
     *
     * Bases greater than 36 are not supported by this method, as there is no clear consensus on which of the lowercase
     * or uppercase characters should come first. Instead, this method accepts any base up to 36, and does not
     * differentiate lowercase and uppercase characters, which are considered equal.
     *
     * For bases greater than 36, and/or custom alphabets, use the fromArbitraryBase() method.
     *
     * @param string $number The number to convert, in the given base.
     * @param int    $base   The base of the number, between 2 and 36.
     *
     * @return BigInteger
     *
     * @throws NumberFormatException     If the number is empty, or contains invalid chars for the given base.
     * @throws \InvalidArgumentException If the base is out of range.
     */
    public static function fromBase(string $number, int $base) : BigInteger
    {
        if ($number === '') {
            throw new NumberFormatException('The number cannot be empty.');
        }

        if ($base < 2 || $base > 36) {
            throw new \InvalidArgumentException(\sprintf('Base %d is not in range 2 to 36.', $base));
        }

        if ($number[0] === '-') {
            $sign = '-';
            $number = \substr($number, 1);
        } elseif ($number[0] === '+') {
            $sign = '';
            $number = \substr($number, 1);
        } else {
            $sign = '';
        }

        if ($number === '') {
            throw new NumberFormatException('The number cannot be empty.');
        }

        $number = \ltrim($number, '0');

        if ($number === '') {
            // The result will be the same in any base, avoid further calculation.
            return BigInteger::zero();
        }

        if ($number === '1') {
            // The result will be the same in any base, avoid further calculation.
            return new BigInteger($sign . '1');
        }

        $pattern = '/[^' . \substr(Calculator::ALPHABET, 0, $base) . ']/';

        if (\preg_match($pattern, \strtolower($number), $matches) === 1) {
            throw new NumberFormatException(\sprintf('"%s" is not a valid character in base %d.', $matches[0], $base));
        }

        if ($base === 10) {
            // The number is usable as is, avoid further calculation.
            return new BigInteger($sign . $number);
        }

        $result = Calculator::get()->fromBase($number, $base);

        return new BigInteger($sign . $result);
    }

    /**
     * Parses a string containing an integer in an arbitrary base, using a custom alphabet.
     *
     * Because this method accepts an alphabet with any character, including dash, it does not handle negative numbers.
     *
     * @param string $number   The number to parse.
     * @param string $alphabet The alphabet, for example '01' for base 2, or '01234567' for base 8.
     *
     * @return BigInteger
     *
     * @throws NumberFormatException     If the given number is empty or contains invalid chars for the given alphabet.
     * @throws \InvalidArgumentException If the alphabet does not contain at least 2 chars.
     */
    public static function fromArbitraryBase(string $number, string $alphabet) : BigInteger
    {
        if ($number === '') {
            throw new NumberFormatException('The number cannot be empty.');
        }

        $base = \strlen($alphabet);

        if ($base < 2) {
            throw new \InvalidArgumentException('The alphabet must contain at least 2 chars.');
        }

        $pattern = '/[^' . \preg_quote($alphabet, '/') . ']/';

        if (\preg_match($pattern, $number, $matches) === 1) {
            throw NumberFormatException::charNotInAlphabet($matches[0]);
        }

        $number = Calculator::get()->fromArbitraryBase($number, $alphabet, $base);

        return new BigInteger($number);
    }

    /**
     * Returns a BigInteger representing zero.
     *
     * @return BigInteger
     */
    public static function zero() : BigInteger
    {
        static $zero;

        if ($zero === null) {
            $zero = new BigInteger('0');
        }

        return $zero;
    }

    /**
     * Returns a BigInteger representing one.
     *
     * @return BigInteger
     */
    public static function one() : BigInteger
    {
        static $one;

        if ($one === null) {
            $one = new BigInteger('1');
        }

        return $one;
    }

    /**
     * Returns a BigInteger representing ten.
     *
     * @return BigInteger
     */
    public static function ten() : BigInteger
    {
        static $ten;

        if ($ten === null) {
            $ten = new BigInteger('10');
        }

        return $ten;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * @param BigNumber|number|string $that The number to add. Must be convertible to a BigInteger.
     *
     * @return BigInteger The result.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigInteger.
     */
    public function plus($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            return $this;
        }

        if ($this->value === '0') {
            return $that;
        }

        $value = Calculator::get()->add($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * @param BigNumber|number|string $that The number to subtract. Must be convertible to a BigInteger.
     *
     * @return BigInteger The result.
     *
     * @throws MathException If the number is not valid, or is not convertible to a BigInteger.
     */
    public function minus($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            return $this;
        }

        $value = Calculator::get()->sub($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * @param BigNumber|number|string $that The multiplier. Must be convertible to a BigInteger.
     *
     * @return BigInteger The result.
     *
     * @throws MathException If the multiplier is not a valid number, or is not convertible to a BigInteger.
     */
    public function multipliedBy($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($this->value === '1') {
            return $that;
        }

        $value = Calculator::get()->mul($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the result of the division of this number by the given one.
     *
     * @param BigNumber|number|string $that         The divisor. Must be convertible to a BigInteger.
     * @param int                     $roundingMode An optional rounding mode.
     *
     * @return BigInteger The result.
     *
     * @throws MathException If the divisor is not a valid number, is not convertible to a BigInteger, is zero,
     *                       or RoundingMode::UNNECESSARY is used and the remainder is not zero.
     */
    public function dividedBy($that, int $roundingMode = RoundingMode::UNNECESSARY) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $result = Calculator::get()->divRound($this->value, $that->value, $roundingMode);

        return new BigInteger($result);
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * @param int $exponent The exponent.
     *
     * @return BigInteger The result.
     *
     * @throws \InvalidArgumentException If the exponent is not in the range 0 to 1,000,000.
     */
    public function power(int $exponent) : BigInteger
    {
        if ($exponent === 0) {
            return BigInteger::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw new \InvalidArgumentException(\sprintf(
                'The exponent %d is not in the range 0 to %d.',
                $exponent,
                Calculator::MAX_POWER
            ));
        }

        return new BigInteger(Calculator::get()->pow($this->value, $exponent));
    }

    /**
     * Returns the quotient of the division of this number by the given one.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return BigInteger
     *
     * @throws DivisionByZeroException If the divisor is zero.
     */
    public function quotient($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $quotient = Calculator::get()->divQ($this->value, $that->value);

        return new BigInteger($quotient);
    }

    /**
     * Returns the remainder of the division of this number by the given one.
     *
     * The remainder, when non-zero, has the same sign as the dividend.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return BigInteger
     *
     * @throws DivisionByZeroException If the divisor is zero.
     */
    public function remainder($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $remainder = Calculator::get()->divR($this->value, $that->value);

        return new BigInteger($remainder);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return BigInteger[] An array containing the quotient and the remainder.
     *
     * @throws DivisionByZeroException If the divisor is zero.
     */
    public function quotientAndRemainder($that) : array
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        [$quotient, $remainder] = Calculator::get()->divQR($this->value, $that->value);

        return [
            new BigInteger($quotient),
            new BigInteger($remainder)
        ];
    }

    /**
     * Returns the modulo of this number and the given one.
     *
     * The modulo operation yields the same result as the remainder operation when both operands are of the same sign,
     * and may differ when signs are different.
     *
     * The result of the modulo operation, when non-zero, has the same sign as the divisor.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return BigInteger
     *
     * @throws DivisionByZeroException If the divisor is zero.
     */
    public function mod($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        return $this->remainder($that)->plus($that)->remainder($that);
    }

    /**
     * Returns this number raised into power with modulo.
     *
     * This operation only works on positive numbers.
     *
     * Algorithm from: https://www.geeksforgeeks.org/modular-exponentiation-power-in-modular-arithmetic/
     *
     * @param BigNumber|number|string $exp The positive exponent.
     * @param BigNumber|number|string $mod The modulo. Must not be zero.
     *
     * @return BigInteger
     *
     * @throws NegativeNumberException If any of the operands is negative.
     * @throws DivisionByZeroException If the modulo is zero.
     */
    public function powerMod($exp, $mod) : BigInteger
    {
        $exp = BigInteger::of($exp);
        $mod = BigInteger::of($mod);

        if ($this->isNegative() || $exp->isNegative() || $mod->isNegative()) {
            throw new NegativeNumberException('The operands cannot be negative.');
        }

        if ($mod->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $result = Calculator::get()->powmod($this->value, $exp->value, $mod->value);

        return new BigInteger($result);
    }

    /**
     * Returns the greatest common divisor of this number and the given one.
     *
     * The GCD is always positive, unless both operands are zero, in which case it is zero.
     *
     * @param BigNumber|number|string $that The operand. Must be convertible to an integer number.
     *
     * @return BigInteger
     */
    public function gcd($that) : BigInteger
    {
        $that = BigInteger::of($that);

        if ($that->value === '0' && $this->value[0] !== '-') {
            return $this;
        }

        if ($this->value === '0' && $that->value[0] !== '-') {
            return $that;
        }

        $value = Calculator::get()->gcd($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the integer square root number of this number, rounded down.
     *
     * The result is the largest x such that x² ≤ n.
     *
     * @return BigInteger
     *
     * @throws NegativeNumberException If this number is negative.
     */
    public function sqrt() : BigInteger
    {
        if ($this->value[0] === '-') {
            throw new NegativeNumberException('Cannot calculate the square root of a negative number.');
        }

        $value = Calculator::get()->sqrt($this->value);

        return new BigInteger($value);
    }

    /**
     * Returns the absolute value of this number.
     *
     * @return BigInteger
     */
    public function abs() : BigInteger
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the inverse of this number.
     *
     * @return BigInteger
     */
    public function negated() : BigInteger
    {
        return new BigInteger(Calculator::get()->neg($this->value));
    }

    /**
     * Returns the integer bitwise-and combined with another integer.
     *
     * This method returns a negative BigInteger if and only if both operands are negative.
     *
     * @param BigNumber|number|string $that The operand. Must be convertible to an integer number.
     *
     * @return BigInteger
     */
    public function and($that) : BigInteger
    {
        $that = BigInteger::of($that);

        return new BigInteger(Calculator::get()->and($this->value, $that->value));
    }

    /**
     * Returns the integer bitwise-or combined with another integer.
     *
     * This method returns a negative BigInteger if and only if either of the operands is negative.
     *
     * @param BigNumber|number|string $that The operand. Must be convertible to an integer number.
     *
     * @return BigInteger
     */
    public function or($that) : BigInteger
    {
        $that = BigInteger::of($that);

        return new BigInteger(Calculator::get()->or($this->value, $that->value));
    }

    /**
     * Returns the integer bitwise-xor combined with another integer.
     *
     * This method returns a negative BigInteger if and only if exactly one of the operands is negative.
     *
     * @param BigNumber|number|string $that The operand. Must be convertible to an integer number.
     *
     * @return BigInteger
     */
    public function xor($that) : BigInteger
    {
        $that = BigInteger::of($that);

        return new BigInteger(Calculator::get()->xor($this->value, $that->value));
    }

    /**
     * Returns the integer left shifted by a given number of bits.
     *
     * @param int $distance The distance to shift.
     *
     * @return BigInteger
     */
    public function shiftedLeft(int $distance) : BigInteger
    {
        if ($distance === 0) {
            return $this;
        }

        if ($distance < 0) {
            return $this->shiftedRight(- $distance);
        }

        return $this->multipliedBy(BigInteger::of(2)->power($distance));
    }

    /**
     * Returns the integer right shifted by a given number of bits.
     *
     * @param int $distance The distance to shift.
     *
     * @return BigInteger
     */
    public function shiftedRight(int $distance) : BigInteger
    {
        if ($distance === 0) {
            return $this;
        }

        if ($distance < 0) {
            return $this->shiftedLeft(- $distance);
        }

        $operand = BigInteger::of(2)->power($distance);

        if ($this->isPositiveOrZero()) {
            return $this->quotient($operand);
        }

        return $this->dividedBy($operand, RoundingMode::UP);
    }

    /**
     * {@inheritdoc}
     */
    public function compareTo($that) : int
    {
        $that = BigNumber::of($that);

        if ($that instanceof BigInteger) {
            return Calculator::get()->cmp($this->value, $that->value);
        }

        return - $that->compareTo($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getSign() : int
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigInteger() : BigInteger
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toBigDecimal() : BigDecimal
    {
        return BigDecimal::create($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigRational() : BigRational
    {
        return BigRational::create($this, BigInteger::one(), false);
    }

    /**
     * {@inheritdoc}
     */
    public function toScale(int $scale, int $roundingMode = RoundingMode::UNNECESSARY) : BigDecimal
    {
        return $this->toBigDecimal()->toScale($scale, $roundingMode);
    }

    /**
     * {@inheritdoc}
     */
    public function toInt() : int
    {
        $intValue = (int) $this->value;

        if ($this->value !== (string) $intValue) {
            throw IntegerOverflowException::toIntOverflow($this);
        }

        return $intValue;
    }

    /**
     * {@inheritdoc}
     */
    public function toFloat() : float
    {
        return (float) $this->value;
    }

    /**
     * Returns a string representation of this number in the given base.
     *
     * The output will always be lowercase for bases greater than 10.
     *
     * @param int $base
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the base is out of range.
     */
    public function toBase(int $base) : string
    {
        if ($base === 10) {
            return $this->value;
        }

        if ($base < 2 || $base > 36) {
            throw new \InvalidArgumentException(\sprintf('Base %d is out of range [2, 36]', $base));
        }

        return Calculator::get()->toBase($this->value, $base);
    }

    /**
     * Returns a string representation of this number in an arbitrary base with a custom alphabet.
     *
     * Because this method accepts an alphabet with any character, including dash, it does not handle negative numbers;
     * a NegativeNumberException will be thrown when attempting to call this method on a negative number.
     *
     * @param string $alphabet The alphabet, for example '01' for base 2, or '01234567' for base 8.
     *
     * @return string
     *
     * @throws NegativeNumberException   If this number is negative.
     * @throws \InvalidArgumentException If the given alphabet does not contain at least 2 chars.
     */
    public function toArbitraryBase(string $alphabet) : string
    {
        $base = \strlen($alphabet);

        if ($base < 2) {
            throw new \InvalidArgumentException('The alphabet must contain at least 2 chars.');
        }

        if ($this->value[0] === '-') {
            throw new NegativeNumberException(__FUNCTION__ . '() does not support negative numbers.');
        }

        return Calculator::get()->toArbitraryBase($this->value, $alphabet, $base);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() : string
    {
        return $this->value;
    }

    /**
     * This method is required by interface Serializable and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return string
     */
    public function serialize() : string
    {
        return $this->value;
    }

    /**
     * This method is only here to implement interface Serializable and cannot be accessed directly.
     *
     * @internal
     *
     * @param string $value
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function unserialize($value) : void
    {
        if (isset($this->value)) {
            throw new \LogicException('unserialize() is an internal function, it must not be called directly.');
        }

        $this->value = $value;
    }
}
