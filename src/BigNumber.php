<?php

namespace Brick\Math;

/**
 * Common interface for arbitrary-precision numbers.
 */
interface BigNumber
{
    /**
     * Compares this number to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return integer [-1,0,1]
     */
    public function compareTo($that);

    /**
     * Checks if this number is equal to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isEqualTo($that);

    /**
     * Checks if this number is strictly lower than the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isLessThan($that);

    /**
     * Checks if this number is lower than or equal to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isLessThanOrEqualTo($that);

    /**
     * Checks if this number is strictly greater than the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isGreaterThan($that);

    /**
     * Checks if this number is greater than or equal to the given one.
     *
     * @param BigNumber|number|string $that
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo($that);

    /**
     * Returns the sign of this number.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    public function getSign();

    /**
     * Checks if this number equals zero.
     *
     * @return bool
     */
    public function isZero();

    /**
     * Checks if this number is strictly negative.
     *
     * @return bool
     */
    public function isNegative();

    /**
     * Checks if this number is negative or zero.
     *
     * @return bool
     */
    public function isNegativeOrZero();

    /**
     * Checks if this number is strictly positive.
     *
     * @return bool
     */
    public function isPositive();

    /**
     * Checks if this number is positive or zero.
     *
     * @return bool
     */
    public function isPositiveOrZero();

    /**
     * Converts this number to a BigInteger.
     *
     * @return BigInteger
     *
     * @throws ArithmeticException If this number cannot be safely converted to a BigInteger.
     */
    public function toBigInteger();

    /**
     * Converts this number to a BigDecimal.
     *
     * @return BigDecimal
     *
     * @throws ArithmeticException If this number cannot be safely converted to a BigDecimal.
     */
    public function toBigDecimal();

    /**
     * Converts this number to a BigRational.
     *
     * @return BigRational
     */
    public function toBigRational();

    /**
     * Returns a string representation of this number.
     *
     * @return string
     */
    public function __toString();
}
