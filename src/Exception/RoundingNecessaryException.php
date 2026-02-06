<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a number cannot be represented at the requested scale without rounding.
 */
final class RoundingNecessaryException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function roundingNecessary(): RoundingNecessaryException
    {
        return new self('Rounding is necessary to represent the result of the operation.');
    }

    /**
     * @pure
     */
    public static function scaleRequiresRounding(): RoundingNecessaryException
    {
        return new self('The value cannot be represented at the requested scale without rounding.');
    }

    /**
     * @pure
     */
    public static function integerSquareRootNotExact(): RoundingNecessaryException
    {
        return new self('The square root is not exact and cannot be represented as an integer without rounding.');
    }

    /**
     * @pure
     */
    public static function decimalSquareRootNotExact(): RoundingNecessaryException
    {
        return new self('The square root is not exact and cannot be represented as a decimal number without rounding.');
    }

    /**
     * @pure
     */
    public static function decimalSquareRootScaleTooSmall(): RoundingNecessaryException
    {
        return new self('The square root is exact but cannot be represented at the requested scale without rounding.');
    }

    /**
     * @pure
     */
    public static function nonTerminatingDecimal(): RoundingNecessaryException
    {
        return new self('The division yields a non-terminating decimal expansion.');
    }

    /**
     * @pure
     */
    public static function decimalNotConvertibleToInteger(): RoundingNecessaryException
    {
        return new self('This decimal number cannot be represented exactly as an integer.');
    }

    /**
     * @pure
     */
    public static function rationalNotConvertibleToInteger(): RoundingNecessaryException
    {
        return new self('This rational number cannot be represented exactly as an integer.');
    }

    /**
     * @pure
     */
    public static function rationalNotConvertibleToDecimal(): RoundingNecessaryException
    {
        return new self('This rational number cannot be represented exactly as a decimal.');
    }
}
