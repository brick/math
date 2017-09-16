<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use Brick\Math\BigInteger;

/**
 * Exception thrown when an integer overflow occurs.
 */
class IntegerOverflowException extends ArithmeticException
{
    /**
     * @param BigInteger $value
     *
     * @return ArithmeticException
     */
    public static function toIntOverflow(BigInteger $value) : ArithmeticException
    {
        $message = '%s is out of range %d to %d and cannot be represented as an integer.';

        return new self(sprintf($message, (string) $value, PHP_INT_MIN, PHP_INT_MAX));
    }
}
