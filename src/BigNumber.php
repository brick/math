<?php

namespace Brick\Math;

/**
 * Common interface for arbitrary-precision numbers.
 */
abstract class BigNumber
{
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
