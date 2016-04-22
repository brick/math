<?php

namespace Brick\Math\Exception;

/**
 * Exception thrown when a division by zero occurs.
 */
class DivisionByZeroException extends ArithmeticException
{
    /**
     * @return DivisionByZeroException
     */
    public static function divisionByZero()
    {
        return new self('Division by zero.');
    }

    /**
     * @return DivisionByZeroException
     */
    public static function denominatorMustNotBeZero()
    {
        return new self('The denominator of a rational number cannot be zero.');
    }
}
