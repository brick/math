<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use function sprintf;

/**
 * Exception thrown when an invalid argument is provided.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements MathException
{
    /**
     * @pure
     */
    public static function baseOutOfRange(int $base): InvalidArgumentException
    {
        return new self(sprintf('Base %d is out of range [2, 36].', $base));
    }

    /**
     * @pure
     */
    public static function negativeScale(): InvalidArgumentException
    {
        return new self('The scale must not be negative.');
    }

    /**
     * @pure
     */
    public static function negativeBitIndex(): InvalidArgumentException
    {
        return new self('The bit index must not be negative.');
    }

    /**
     * @pure
     */
    public static function negativeBitCount(): InvalidArgumentException
    {
        return new self('The bit count must not be negative.');
    }

    /**
     * @pure
     */
    public static function alphabetTooShort(): InvalidArgumentException
    {
        return new self('The alphabet must contain at least 2 characters.');
    }

    /**
     * @pure
     */
    public static function duplicateCharsInAlphabet(): InvalidArgumentException
    {
        return new self('The alphabet must not contain duplicate characters.');
    }

    /**
     * @pure
     */
    public static function minGreaterThanMax(): InvalidArgumentException
    {
        return new self('The minimum value must be less than or equal to the maximum value.');
    }

    /**
     * @pure
     */
    public static function negativeExponent(): InvalidArgumentException
    {
        return new self('The exponent must not be negative.');
    }

    /**
     * @pure
     */
    public static function negativeModulus(): InvalidArgumentException
    {
        return new self('The modulus must be strictly positive.');
    }
}
