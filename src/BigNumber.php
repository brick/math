<?php

namespace Brick\Math;

/**
 * Common interface for arbitrary-precision numbers.
 */
interface BigNumber
{
    /**
     * @return BigInteger
     *
     * @throws ArithmeticException If this number cannot be safely converted to a BigInteger.
     */
    public function toBigInteger();

    /**
     * @return BigDecimal
     *
     * @throws ArithmeticException If this number cannot be safely converted to a BigDecimal.
     */
    public function toBigDecimal();

    /**
     * @return BigRational
     */
    public function toBigRational();
}
