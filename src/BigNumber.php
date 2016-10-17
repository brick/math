<?php

namespace Brick\Math;

use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Common interface for arbitrary-precision rational numbers.
 */
abstract class BigNumber implements \Serializable, \JsonSerializable
{
    /**
     * The regular expression used to parse integer, decimal and rational numbers.
     *
     * @var string
     */
    private static $regexp =
        '/^' .
        '(?<integral>[\-\+]?[0-9]+)' .
        '(?:' .
            '(?:' .
                '(?:\.(?<fractional>[0-9]+))?' .
                '(?:[eE](?<exponent>[\-\+]?[0-9]+))?' .
            ')' . '|' . '(?:' .
                '(?:\/(?<denominator>[0-9]+))?' .
            ')' .
        ')?' .
        '$/';

    /**
     * Creates a BigNumber of the given value.
     *
     * The concrete return type is dependent on the given value, with the following rules:
     *
     * - BigNumber instances are returned as is
     * - integer numbers are returned as BigInteger
     * - floating point numbers are returned as BigDecimal
     * - strings containing a `/` character are returned as BigRational
     * - strings containing a `.` character or using an exponentional notation are returned as BigDecimal
     * - strings containing only digits with an optional leading `+` or `-` sign are returned as BigInteger
     *
     * @param BigNumber|number|string $value
     *
     * @return BigNumber
     *
     * @throws NumberFormatException   If the format of the number is not valid.
     * @throws DivisionByZeroException If the value represents a rational number with a denominator of zero.
     */
    public static function of($value)
    {
        if ($value instanceof BigNumber) {
            return $value;
        }

        if (is_int($value)) {
            return new BigInteger((string) $value);
        }

        $value = (string) $value;

        if (preg_match(self::$regexp, $value, $matches) !== 1) {
            throw new NumberFormatException('The given value does not represent a valid number.');
        }

        if (isset($matches['denominator'])) {
            $numerator   = self::cleanUp($matches['integral']);
            $denominator = ltrim($matches['denominator'], '0');

            if ($denominator === '') {
                throw DivisionByZeroException::denominatorMustNotBeZero();
            }

            return new BigRational(new BigInteger($numerator), new BigInteger($denominator), false);
        }

        if (isset($matches['fractional']) || isset($matches['exponent'])) {
            $fractional = isset($matches['fractional']) ? $matches['fractional'] : '';
            $exponent = isset($matches['exponent']) ? (int) $matches['exponent'] : 0;

            $unscaledValue = self::cleanUp($matches['integral'] . $fractional);

            $scale = strlen($fractional) - $exponent;

            if ($scale < 0) {
                if ($unscaledValue !== '0') {
                    $unscaledValue .= str_repeat('0', - $scale);
                }
                $scale = 0;
            }

            return new BigDecimal($unscaledValue, $scale);
        }

        $integral = self::cleanUp($matches['integral']);

        return new BigInteger($integral);
    }

    /**
     * Proxy method to access protected constructors from sibling classes.
     *
     * @internal
     *
     * @param mixed ...$args The arguments to the constructor.
     *
     * @return static
     */
    protected static function create(... $args)
    {
        return new static(... $args);
    }

    /**
     * Returns the minimum of the given values.
     *
     * @param BigNumber|number|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                           to an instance of the class this method is called on.
     *
     * @return static The minimum value.
     *
     * @throws \InvalidArgumentException If no values are given.
     * @throws ArithmeticException       If an argument is not valid.
     */
    public static function min(...$values)
    {
        $min = null;

        foreach ($values as $value) {
            $value = static::of($value);

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
     * @param BigNumber|number|string ...$values The numbers to compare. All the numbers need to be convertible
     *                                           to an instance of the class this method is called on.
     *
     * @return static The maximum value.
     *
     * @throws \InvalidArgumentException If no values are given.
     * @throws ArithmeticException       If an argument is not valid.
     */
    public static function max(...$values)
    {
        $max = null;

        foreach ($values as $value) {
            $value = static::of($value);

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
     * Removes optional leading zeros and + sign from the given number.
     *
     * @param string $number The number, validated as a non-empty string of digits with optional sign.
     *
     * @return string
     */
    private static function cleanUp($number)
    {
        $firstChar = $number[0];

        if ($firstChar === '+' || $firstChar === '-') {
            $number = substr($number, 1);
        }

        $number = ltrim($number, '0');

        if ($number === '') {
            return '0';
        }

        if ($firstChar === '-') {
            return '-' . $number;
        }

        return $number;
    }

    /**
     * Checks if this number is equal to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isEqualTo($that)
    {
        return $this->compareTo($that) == 0;
    }

    /**
     * Checks if this number is strictly lower than the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isLessThan($that)
    {
        return $this->compareTo($that) < 0;
    }

    /**
     * Checks if this number is lower than or equal to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isLessThanOrEqualTo($that)
    {
        return $this->compareTo($that) <= 0;
    }

    /**
     * Checks if this number is strictly greater than the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isGreaterThan($that)
    {
        return $this->compareTo($that) > 0;
    }

    /**
     * Checks if this number is greater than or equal to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo($that)
    {
        return $this->compareTo($that) >= 0;
    }

    /**
     * Checks if this number equals zero.
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->sign() == 0;
    }

    /**
     * Checks if this number is strictly negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->sign() < 0;
    }

    /**
     * Checks if this number is negative or zero.
     *
     * @return bool
     */
    public function isNegativeOrZero()
    {
        return $this->sign() <= 0;
    }

    /**
     * Checks if this number is strictly positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->sign() > 0;
    }

    /**
     * Checks if this number is positive or zero.
     *
     * @return bool
     */
    public function isPositiveOrZero()
    {
        return $this->sign() >= 0;
    }

    /**
     * Returns the sign of this number.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    abstract public function sign();

    /**
     * Compares this number to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return int [-1,0,1] If `$this` is lower than, equal to, or greater than `$that`.
     *
     * @throws ArithmeticException If the number is not valid.
     */
    abstract public function compareTo($that);

    /**
     * Converts this number to a BigInteger.
     *
     * @return BigInteger The converted number.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to a BigInteger without rounding.
     */
    abstract public function toBigInteger();

    /**
     * Converts this number to a BigDecimal.
     *
     * @return BigDecimal The converted number.
     *
     * @throws RoundingNecessaryException If this number cannot be converted to a BigDecimal without rounding.
     */
    abstract public function toBigDecimal();

    /**
     * Converts this number to a BigRational.
     *
     * @return BigRational The converted number.
     */
    abstract public function toBigRational();

    /**
     * Converts this number to a BigDecimal with the given scale, using rounding if necessary.
     *
     * @param int $scale        The scale of the resulting `BigDecimal`.
     * @param int $roundingMode A `RoundingMode` constant.
     *
     * @return BigDecimal
     */
    abstract public function toScale($scale, $roundingMode = RoundingMode::UNNECESSARY);

    /**
     * Returns the exact value of this number as a native integer.
     *
     * If this number cannot be converted to a native integer without losing precision, an exception is thrown.
     * Note that the acceptable range for an integer depends on the platform and differs for 32-bit and 64-bit.
     *
     * @return int The converted value.
     *
     * @throws ArithmeticException If this number cannot be exactly converted to a native integer.
     */
    abstract public function toInteger();

    /**
     * Returns an approximation of this number as a floating-point value.
     *
     * Note that this method can discard information as the precision of a floating-point value
     * is inherently limited.
     *
     * @return float The converted value.
     */
    abstract public function toFloat();

    /**
     * Returns a string representation of this number.
     *
     * The output of this method can be parsed by the `of()` factory method;
     * this will yield an object equal to this one, without any information loss.
     *
     * @return string
     */
    abstract public function __toString();

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }
}
