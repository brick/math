<?php

namespace Brick\Math\Exception;

use Brick\Math\BigInteger;

/**
 * Exception thrown when arithmetic operations fail.
 */
class ArithmeticException extends \RuntimeException
{
    /**
     * @param BigInteger $value
     *
     * @return ArithmeticException
     */
    public static function integerOverflow(BigInteger $value)
    {
        $message = '%s is out of range %d to %d and cannot be represented as an integer.';

        return new self(sprintf($message, (string) $value, ~PHP_INT_MAX, PHP_INT_MAX));
    }
}
