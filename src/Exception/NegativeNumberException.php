<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

use function sprintf;

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
    public static function negativeModulus(): NegativeNumberException
    {
        return new self('Modulus must be strictly positive.');
    }

    /**
     * @pure
     */
    public static function negativeExponent(): NegativeNumberException
    {
        return new self('Exponent must not be negative.');
    }

    /**
     * @pure
     */
    public static function notSupportedForNegativeNumber(string $method): NegativeNumberException
    {
        return new self(sprintf('%s() does not support negative numbers.', $method));
    }

    /**
     * @pure
     */
    public static function unsignedBytesOfNegativeNumber(): NegativeNumberException
    {
        return new self('Cannot convert a negative number to a byte string in unsigned mode.');
    }
}
