<?php

namespace Brick\Math;

/**
 * Common interface for arbitrary-precision numbers.
 */
interface BigNumber
{
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
