<?php

namespace Brick\Math;

use Brick\Math\Internal\Calculator;

/**
 * An arbitrary-size integer.
 *
 * All methods accepting a number as a parameter accept either a BigInteger instance,
 * an integer, or a string representing an arbitrary size integer.
 */
class BigInteger extends BigNumber implements \Serializable
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
     * Protected constructor. Use the factory methods.
     *
     * @param string $value A string of digits, with optional leading minus sign.
     */
    protected function __construct($value)
    {
        $this->value = $value;
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
            return new BigInteger('0');
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
     * This value is cached to optimize memory consumption as it is frequently used.
     *
     * @return BigInteger
     */
    public static function zero()
    {
        static $zero = null;

        if ($zero === null) {
            $zero = new BigInteger('0');
        }

        return $zero;
    }

    /**
     * Returns the minimum of the given values.
     *
     * @param BigInteger|int|string ...$values The numbers to compare.
     *
     * @return BigInteger The minimum value.
     *
     * @throws \InvalidArgumentException If no values are given, or an invalid value is given.
     */
    public static function min(...$values)
    {
        $min = null;

        foreach ($values as $value) {
            $value = BigInteger::of($value);
            if ($min === null || $value->isLessThan($min)) {
                $min = $value;
            }
        }

        if ($min === null) {
            throw new \InvalidArgumentException(__METHOD__ . '() expects at least one value.');
        }

        return $min;
    }

    /**
     * Returns the maximum of the given values.
     *
     * @param BigInteger|int|string ...$values The numbers to compare.
     *
     * @return BigInteger The maximum value.
     *
     * @throws \InvalidArgumentException If no values are given, or an invalid value is given.
     */
    public static function max(...$values)
    {
        $max = null;

        foreach ($values as $value) {
            $value = BigInteger::of($value);
            if ($max === null || $value->isGreaterThan($max)) {
                $max = $value;
            }
        }

        if ($max === null) {
            throw new \InvalidArgumentException(__METHOD__ . '() expects at least one value.');
        }

        return $max;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return BigInteger
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
     * @param BigInteger|int|string $that
     *
     * @return BigInteger
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
     * Returns the result of the multiplication of this number and the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return BigInteger
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
     * Returns the quotient of the division of this number and the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return BigInteger
     *
     * @throws ArithmeticException If the divisor is zero.
     */
    public function dividedBy($that)
    {
        $that = BigInteger::of($that);

        if ($that->value === '1') {
            return $this;
        }

        if ($that->value === '0') {
            throw ArithmeticException::divisionByZero();
        }

        list ($quotient) = Calculator::get()->div($this->value, $that->value);

        return new BigInteger($quotient);
    }

    /**
     * Returns the quotient and remainder of the division of this number and the given one.
     *
     * @param BigInteger|int|string $that The divisor.
     *
     * @return BigInteger[] An array containing the quotient and the remainder.
     *
     * @throws ArithmeticException If the divisor is zero.
     */
    public function divideAndRemainder($that)
    {
        $that = BigInteger::of($that);

        if ($that->value === '0') {
            throw ArithmeticException::divisionByZero();
        }

        list ($quotient, $remainder) = Calculator::get()->div($this->value, $that->value);

        return [
            new BigInteger($quotient),
            new BigInteger($remainder)
        ];
    }

    /**
     * Returns the remainder of the division of this number and the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return BigInteger
     */
    public function remainder($that)
    {
        $that = BigInteger::of($that);

        list (, $remainder) = Calculator::get()->div($this->value, $that->value);

        return new BigInteger($remainder);
    }

    /**
     * Returns this number exponentiated.
     *
     * The exponent has a limit of 1 million.
     *
     * @param int $exponent The exponent, between 0 and 1,000,000.
     *
     * @return BigInteger
     *
     * @throws \InvalidArgumentException If the exponent is not in the allowed range.
     */
    public function power($exponent)
    {
        $exponent = (int) $exponent;

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
     * Returns the greatest common divisor of this number and the given one.
     *
     * The GCD is always positive.
     *
     * @param BigInteger|int|string $that
     *
     * @return BigInteger
     */
    public function gcd($that)
    {
        $that = BigInteger::of($that);

        if ($that->isZero()) {
            return $this->abs();
        }

        if ($this->isZero()) {
            return $that->abs();
        }

        return $that->gcd($this->remainder($that));
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
        $that = BigInteger::of($that);

        return Calculator::get()->cmp($this->value, $that->value);
    }

    /**
     * {@inheritdoc}
     */
    public function getSign()
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
        return BigDecimal::of($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigRational()
    {
        return BigRational::nd($this->value, 1);
    }

    /**
     * Converts this BigInteger to an integer.
     *
     * @return int The integer value.
     *
     * @throws ArithmeticException If this BigInteger exceeds the capacity of an integer.
     */
    public function toInteger()
    {
        if ($this->isLessThan(~PHP_INT_MAX) || $this->isGreaterThan(PHP_INT_MAX)) {
            throw ArithmeticException::integerOverflow($this);
        }

        return (int) $this->value;
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
            list ($value, $remainder) = $calc->div($value, $base);
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
