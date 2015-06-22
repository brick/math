<?php

namespace Brick\Math;

use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\DivisionByZeroException;

/**
 * Common interface for arbitrary-precision numbers.
 */
abstract class BigNumber
{
    /**
     * The regular expression used to parse integer, decimal and rational numbers.
     */
    const REGEXP =
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
     * @return static
     *
     * @throws \InvalidArgumentException If the number is not valid.
     * @throws DivisionByZeroException   If the value represents a rational number with a denominator of zero.
     */
    public static function of($value)
    {
        if ($value instanceof BigNumber) {
            try {
                switch (static::class) {
                    case BigInteger::class:
                        return $value->toBigInteger();

                    case BigDecimal::class:
                        return $value->toBigDecimal();

                    case BigRational::class:
                        return $value->toBigRational();

                    default:
                        return $value;
                }
            } catch (ArithmeticException $e) {
                $className = substr(static::class, strrpos(static::class, '\\') + 1);

                throw new \InvalidArgumentException('Cannot convert value to a ' . $className . ' without losing precision.');
            }
        }

        if (is_int($value)) {
            switch (static::class) {
                case BigDecimal::class:
                    return new BigDecimal((string) $value);

                case BigRational::class:
                    return new BigRational(new BigInteger((string) $value), new BigInteger('1'));

                default:
                    return new BigInteger((string) $value);
            }
        }

        $value = (string) $value;

        if (preg_match(BigNumber::REGEXP, $value, $matches) !== 1) {
            throw new \InvalidArgumentException('The given value does not represent a valid number.');
        }

        if (isset($matches['denominator'])) {
            $numerator   = BigNumber::cleanUp($matches['integral']);
            $denominator = ltrim($matches['denominator'], '0');

            if ($denominator === '') {
                throw DivisionByZeroException::denominatorMustNotBeZero();
            }

            $result = new BigRational(new BigInteger($numerator), new BigInteger($denominator), false);

            try {
                switch (static::class) {
                    case BigInteger::class:
                        return $result->toBigInteger();

                    case BigDecimal::class:
                        return $result->toBigDecimal();

                    default:
                        return $result;
                }
            } catch (ArithmeticException $e) {
                $className = substr(static::class, strrpos(static::class, '\\') + 1);

                throw new \InvalidArgumentException('Cannot convert value to a ' . $className . ' without losing precision.');
            }
        } elseif (isset($matches['fractional']) || isset($matches['exponent'])) {
            $fractional = isset($matches['fractional']) ? $matches['fractional'] : '';
            $exponent = isset($matches['exponent']) ? (int) $matches['exponent'] : 0;

            $unscaledValue = BigNumber::cleanUp($matches['integral'] . $fractional);

            $scale = strlen($fractional) - $exponent;

            if ($scale < 0) {
                if ($unscaledValue !== '0') {
                    $unscaledValue .= str_repeat('0', - $scale);
                }
                $scale = 0;
            }

            $result = new BigDecimal($unscaledValue, $scale);

            try {
                switch (static::class) {
                    case BigInteger::class:
                        return $result->toBigInteger();

                    case BigRational::class:
                        return $result->toBigRational();

                    default:
                        return $result;
                }
            } catch (ArithmeticException $e) {
                $className = substr(static::class, strrpos(static::class, '\\') + 1);

                throw new \InvalidArgumentException('Cannot convert value to a ' . $className . ' without losing precision.');
            }
        } else {
            $integral = BigNumber::cleanUp($matches['integral']);

            switch (static::class) {
                case BigDecimal::class:
                    return new BigDecimal($integral);

                case BigRational::class:
                    return new BigRational(new BigInteger($integral), new BigInteger('1'), false);

                default:
                    return new BigInteger($integral);
            }
        }
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
     * Compares this number to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return int [-1,0,1]
     */
    abstract public function compareTo($that);

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
     * Returns the sign of this number.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    abstract public function getSign();

    /**
     * Checks if this number equals zero.
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->getSign() == 0;
    }

    /**
     * Checks if this number is strictly negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->getSign() < 0;
    }

    /**
     * Checks if this number is negative or zero.
     *
     * @return bool
     */
    public function isNegativeOrZero()
    {
        return $this->getSign() <= 0;
    }

    /**
     * Checks if this number is strictly positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->getSign() > 0;
    }

    /**
     * Checks if this number is positive or zero.
     *
     * @return bool
     */
    public function isPositiveOrZero()
    {
        return $this->getSign() >= 0;
    }

    /**
     * Converts this number to a BigInteger.
     *
     * @return BigInteger
     *
     * @throws ArithmeticException If this number cannot be safely converted to a BigInteger.
     */
    abstract public function toBigInteger();

    /**
     * Converts this number to a BigDecimal.
     *
     * @return BigDecimal
     *
     * @throws ArithmeticException If this number cannot be safely converted to a BigDecimal.
     */
    abstract public function toBigDecimal();

    /**
     * Converts this number to a BigRational.
     *
     * @return BigRational
     */
    abstract public function toBigRational();

    /**
     * Returns a string representation of this number.
     *
     * @return string
     */
    abstract public function __toString();
}
