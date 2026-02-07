<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to perform an unsupported operation, such as a square root, on a negative number.
 */
final class NegativeNumberException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function squareRootOfNegativeNumber(): NegativeNumberException
    {
        return new self('Cannot calculate the square root of a negative number.');
    }

    /**
     * @pure
     */
    public static function toArbitraryBaseOfNegativeNumber(): NegativeNumberException
    {
        return new self('Cannot convert a negative number to an arbitrary base.');
    }

    /**
     * @pure
     */
    public static function unsignedBytesOfNegativeNumber(): NegativeNumberException
    {
        return new self('Cannot convert a negative number to a byte string in unsigned mode.');
    }
}
