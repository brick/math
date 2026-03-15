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
    public static function baseOutOfRange(int $base): self
    {
        return new self(sprintf('Base %d is out of range [2, 36].', $base));
    }

    /**
     * @pure
     */
    public static function negativeScale(): self
    {
        return new self('The scale must not be negative.');
    }

    /**
     * @pure
     */
    public static function negativeBitIndex(): self
    {
        return new self('The bit index must not be negative.');
    }

    /**
     * @pure
     */
    public static function negativeBitCount(): self
    {
        return new self('The bit count must not be negative.');
    }

    /**
     * @pure
     */
    public static function alphabetTooShort(): self
    {
        return new self('The alphabet must contain at least 2 characters.');
    }

    /**
     * @pure
     */
    public static function duplicateCharsInAlphabet(): self
    {
        return new self('The alphabet must not contain duplicate characters.');
    }

    /**
     * @pure
     */
    public static function minGreaterThanMax(): self
    {
        return new self('The minimum value must be less than or equal to the maximum value.');
    }

    /**
     * @pure
     */
    public static function cannotConvertFloat(string $type): self
    {
        return new self(sprintf('Cannot convert %s to a BigDecimal.', $type));
    }

    /**
     * @pure
     */
    public static function negativeExponent(): self
    {
        return new self('The exponent must not be negative.');
    }

    /**
     * @pure
     */
    public static function negativeModulus(): self
    {
        return new self('The modulus must not be negative.');
    }
}
