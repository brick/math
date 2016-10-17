<?php

namespace Brick\Math;

use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\RoundingNecessaryException;
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
    protected function __construct($value)
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
     * @throws ArithmeticException If the value cannot be converted to a BigInteger.
     */
    public static function of($value)
    {
        return parent::of($value)->toBigInteger();
    }

    /**
     * Parses a string containing an integer in an arbitrary base.
     *
     * The string can optionally be prefixed with the `+` or `-` sign.
     *
     * @param string $number The number to parse.
     * @param int    $base   The base of the number, between 2 and 36.
     *
     * @return BigInteger
     *
     * @throws \InvalidArgumentException If the number is invalid or the base is out of range.
     */
    public static function parse($number, $base = 10)
    {
        $number = (string) $number;
        $base = (int) $base;

        $dictionary = '0123456789abcdefghijklmnopqrstuvwxyz';

        if ($number === '') {
            throw new \InvalidArgumentException('The value cannot be empty.');
        }

        if ($base < 2 || $base > 36) {
            throw new \InvalidArgumentException(sprintf('Base %d is not in range 2 to 36.', $base));
        }

        if ($number[0] === '-') {
            $sign = '-';
            $number = substr($number, 1);
        } elseif ($number[0] === '+') {
            $sign = '';
            $number = substr($number, 1);
        } else {
            $sign = '';
        }

        if ($number === false /* PHP 5 */ || $number === '' /* PHP 7 */) {
            throw new \InvalidArgumentException('The value cannot be empty.');
        }

        $number = ltrim($number, '0');

        if ($number === '') {
            // The result will be the same in any base, avoid further calculation.
            return BigInteger::zero();
        }

        if ($number === '1') {
            // The result will be the same in any base, avoid further calculation.
            return new BigInteger($sign . '1');
        }

        if ($base === 10 && ctype_digit($number)) {
            // The number is usable as is, avoid further calculation.
            return new BigInteger($sign . $number);
        }

        $calc = Calculator::get();
        $number = strtolower($number);

        $result = '0';
        $power = '1';

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $char = $number[$i];
            $index = strpos($dictionary, $char);

            if ($index === false || $index >= $base) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid character in base %d.', $char, $base));
            }

            if ($index !== 0) {
                $add = ($index === 1) ? $power : $calc->mul($power, (string) $index);
                $result = $calc->add($result, $add);
            }

            $power = $calc->mul($power, (string) $base);
        }

        return new BigInteger($sign . $result);
    }

    /**
     * Returns a BigInteger representing zero.
     *
     * @return BigInteger
     */
    public static function zero()
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
    public static function one()
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
    public static function ten()
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
     * @throws ArithmeticException If the number is not valid, or is not convertible to a BigInteger.
     */
    public function plus($that)
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            return $this;
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
     * @throws ArithmeticException If the number is not valid, or is not convertible to a BigInteger.
     */
    public function minus($that)
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
     * @throws ArithmeticException If the multiplier is not a valid number, or is not convertible to a BigInteger.
     */
    public function multipliedBy($that)
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
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
     * @throws ArithmeticException If the divisor is not a valid number, is not convertible to a BigInteger, is zero,
     *                             or RoundingMode::UNNECESSARY is used and the remainder is not zero.
     */
    public function dividedBy($that, $roundingMode = RoundingMode::UNNECESSARY)
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
    public function power($exponent)
    {
        $exponent = (int) $exponent;

        if ($exponent === 0) {
            return BigInteger::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw new \InvalidArgumentException(sprintf(
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
    public function quotient($that)
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
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return BigInteger
     *
     * @throws DivisionByZeroException If the divisor is zero.
     */
    public function remainder($that)
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
    public function quotientAndRemainder($that)
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        list ($quotient, $remainder) = Calculator::get()->divQR($this->value, $that->value);

        return [
            new BigInteger($quotient),
            new BigInteger($remainder)
        ];
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
    public function gcd($that)
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
     * Returns the absolute value of this number.
     *
     * @return BigInteger
     */
    public function abs()
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the inverse of this number.
     *
     * @return BigInteger
     */
    public function negated()
    {
        return new BigInteger(Calculator::get()->neg($this->value));
    }

    /**
     * {@inheritdoc}
     */
    public function compareTo($that)
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
    public function sign()
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigInteger()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toBigDecimal()
    {
        return BigDecimal::create($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigRational()
    {
        return BigRational::create($this, BigInteger::one(), false);
    }

    /**
     * {@inheritdoc}
     */
    public function toScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return $this->toBigDecimal()->toScale($scale, $roundingMode);
    }

    /**
     * {@inheritdoc}
     */
    public function toInteger()
    {
        if ($this->isLessThan(~PHP_INT_MAX) || $this->isGreaterThan(PHP_INT_MAX)) {
            throw ArithmeticException::integerOverflow($this);
        }

        return (int) $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function toFloat()
    {
        return (float) $this->value;
    }

    /**
     * Returns a string representation of this number in the given base.
     *
     * @param int $base
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the base is out of range.
     */
    public function toBase($base)
    {
        $base = (int) $base;

        if ($base === 10) {
            return $this->value;
        }

        if ($base < 2 || $base > 36) {
            throw new \InvalidArgumentException(sprintf('Base %d is out of range [2, 36]', $base));
        }

        $dictionary = '0123456789abcdefghijklmnopqrstuvwxyz';

        $calc = Calculator::get();

        $value = $this->value;
        $negative = ($value[0] === '-');

        if ($negative) {
            $value = substr($value, 1);
        }

        $base = (string) $base;
        $result = '';

        while ($value !== '0') {
            list ($value, $remainder) = $calc->divQR($value, $base);
            $remainder = (int) $remainder;

            $result .= $dictionary[$remainder];
        }

        if ($negative) {
            $result .= '-';
        }

        return strrev($result);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
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
    public function serialize()
    {
        return $this->value;
    }

    /**
     * This method is required by interface Serializable and MUST NOT be accessed directly.
     *
     * @internal
     *
     * @param string $value
     *
     * @return void
     *
     * @throws \LogicException
     */
    public function unserialize($value)
    {
        if ($this->value !== null) {
            throw new \LogicException('unserialize() is an internal function, it must not be called directly.');
        }

        $this->value = $value;
    }
}
