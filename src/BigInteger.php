<?php

namespace Brick\Math;

use Brick\Math\Internal\Calculator;

/**
 * An arbitrary-size integer.
 *
 * All methods accepting a number as a parameter accept either a BigInteger instance,
 * an integer, or a string representing an arbitrary size integer.
 */
class BigInteger implements \Serializable
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
     * Private constructor. Use the factory methods.
     *
     * @param string $value A string of digits, with optional leading minus sign.
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Returns a BigInteger of the given value.
     *
     * The value can be a BigInteger, a native integer,
     * or a string representing an integer in base 10,
     * optionally prefixed with the `+` or `-` sign.
     *
     * @param BigInteger|integer|string $value The value.
     *
     * @return BigInteger
     *
     * @throws \InvalidArgumentException If the argument is not a valid number.
     */
    public static function of($value)
    {
        if ($value instanceof BigInteger) {
            return $value;
        }

        if (is_int($value)) {
            return new BigInteger((string) $value);
        }

        return BigInteger::parse($value);
    }

    /**
     * Parses a string containing an integer in the given base.
     *
     * The string can optionally be prefixed with the `+` or `-` sign.
     *
     * @param string  $number The number to parse.
     * @param integer $base   The base of the number.
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

        if ($number === false) {
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
     * @param array<BigInteger|integer|string> An array of integers to return the minimum value of.
     *
     * @return \Brick\Math\BigInteger The minimum value.
     *
     * @throws \InvalidArgumentException If no values are given, or an invalid value is given.
     */
    public static function min(array $values)
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
     * @param array<BigInteger|integer|string> An array of integers to return the maximum value of.
     *
     * @return \Brick\Math\BigInteger The maximum value.
     *
     * @throws \InvalidArgumentException If no values are given, or an invalid value is given.
     */
    public static function max(array $values)
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
     * @param BigInteger|integer|string $that
     *
     * @return BigInteger
     */
    public function plus($that)
    {
        $that = BigInteger::of($that);
        $value = Calculator::get()->add($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return BigInteger
     */
    public function minus($that)
    {
        $that = BigInteger::of($that);
        $value = Calculator::get()->sub($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the result of the multiplication of this number and the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return BigInteger
     */
    public function multipliedBy($that)
    {
        $that = BigInteger::of($that);
        $value = Calculator::get()->mul($this->value, $that->value);

        return new BigInteger($value);
    }

    /**
     * Returns the result of the division of this number and the given one.
     *
     * @param BigInteger|integer|string $that
     * @param integer                   $roundingMode
     *
     * @return BigInteger
     *
     * @throws ArithmeticException       If the divisor is zero or rounding is necessary.
     * @throws \InvalidArgumentException If the divisor or the rounding mode is invalid.
     */
    public function dividedBy($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $that = BigInteger::of($that);

        if ($that->isZero()) {
            throw ArithmeticException::divisionByZero();
        }

        $calculator = Calculator::get();
        $result = $calculator->divRounded($this->value, $that->value, $roundingMode);

        if ($result === null) {
            throw ArithmeticException::roundingNecessary();
        }

        return new BigInteger($result);
    }

    /**
     * @param BigInteger|integer|string $that The divisor.
     *
     * @return BigInteger[] An array containing the quotient and the remainder.
     *
     * @throws ArithmeticException If the divisor is zero.
     */
    public function divideAndRemainder($that)
    {
        $that = BigInteger::of($that);

        if ($that->isZero()) {
            throw ArithmeticException::divisionByZero();
        }

        list ($quotient, $remainder) = Calculator::get()->div($this->value, $that->value);

        $quotient = new BigInteger($quotient);
        $remainder = new BigInteger($remainder);

        return [$quotient, $remainder];
    }

    /**
     * Returns the absolute value of this number.
     *
     * @return \Brick\Math\BigInteger
     */
    public function abs()
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the inverse of this number.
     *
     * @return \Brick\Math\BigInteger
     */
    public function negated()
    {
        return new BigInteger(Calculator::get()->neg($this->value));
    }

    /**
     * Compares this number to the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return integer [-1,0,1]
     */
    public function compareTo($that)
    {
        $that = BigInteger::of($that);

        return Calculator::get()->cmp($this->value, $that->value);
    }

    /**
     * Checks if this number is equal to the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return boolean
     */
    public function isEqualTo($that)
    {
        return $this->compareTo($that) === 0;
    }

    /**
     * Checks if this number is strictly lower than the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return boolean
     */
    public function isLessThan($that)
    {
        return $this->compareTo($that) < 0;
    }

    /**
     * Checks if this number is lower than or equal to the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return boolean
     */
    public function isLessThanOrEqualTo($that)
    {
        return $this->compareTo($that) <= 0;
    }

    /**
     * Checks if this number is strictly greater than the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return boolean
     */
    public function isGreaterThan($that)
    {
        return $this->compareTo($that) > 0;
    }

    /**
     * Checks if this number is greater than or equal to the given one.
     *
     * @param BigInteger|integer|string $that
     *
     * @return boolean
     */
    public function isGreaterThanOrEqualTo($that)
    {
        return $this->compareTo($that) >= 0;
    }

    /**
     * Checks if this number equals zero.
     *
     * @return boolean
     */
    public function isZero()
    {
        return ($this->value === '0');
    }

    /**
     * Checks if this number is strictly negative.
     *
     * @return boolean
     */
    public function isNegative()
    {
        return ($this->value[0] === '-');
    }

    /**
     * Checks if this number is negative or zero.
     *
     * @return boolean
     */
    public function isNegativeOrZero()
    {
        return ($this->value === '0') || ($this->value[0] === '-');
    }

    /**
     * Checks if this number is strictly positive.
     *
     * @return boolean
     */
    public function isPositive()
    {
        return ($this->value !== '0') && ($this->value[0] !== '-');
    }

    /**
     * Checks if this number is positive or zero.
     *
     * @return boolean
     */
    public function isPositiveOrZero()
    {
        return ($this->value[0] !== '-');
    }

    /**
     * Converts this BigInteger to an integer.
     *
     * @return integer The integer value.
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
     * @todo test with negative numbers, probably broken
     *
     * Returns a string representation of this number in the given base.
     *
     * @param integer $base
     *
     * @return string
     *
     * @throws \InvalidArgumentException If the base is out of range.
     */
    public function toString($base)
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
        $base = (string) $base;
        $result = '';

        while ($value !== '0') {
            list ($value, $remainder) = $calc->div($value, $base);
            $remainder = (int) $remainder;

            $result .= $dictionary[$remainder];
        }

        return strrev($result);
    }

    /**
     * @return string
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
     * Accessing this method directly would bypass consistency checks and break immutability.
     *
     * @internal
     *
     * @param string $value
     *
     * @return void
     */
    public function unserialize($value)
    {
        $this->value = $value;
    }
}
