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
    public static function decimalScaleTooSmall(): RoundingNecessaryException
    {
        return new self('This decimal number cannot be represented at the requested scale without rounding.');
    }

    /**
     * @pure
     */
    public static function rationalScaleTooSmall(): RoundingNecessaryException
    {
        return new self('This rational number cannot be represented at the requested scale without rounding.');
    }

    /**
     * @pure
     */
    public static function integerDivisionNotExact(): RoundingNecessaryException
    {
        return new self('The division has a non-zero remainder and cannot be represented as an integer without rounding.');
    }

    /**
     * @pure
     */
    public static function decimalDivisionNotExact(): RoundingNecessaryException
    {
        return new self('The division yields a non-terminating decimal expansion and cannot be represented as a decimal without rounding.');
    }

    /**
     * @pure
     */
    public static function decimalDivisionScaleTooSmall(): RoundingNecessaryException
    {
        return new self('The division result is exact but cannot be represented at the requested scale without rounding.');
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
        return new self('The square root is not exact and cannot be represented as a decimal without rounding.');
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
    public static function decimalNotConvertibleToInteger(): RoundingNecessaryException
    {
        return new self('This decimal number cannot be represented as an integer without rounding.');
    }

    /**
     * @pure
     */
    public static function rationalNotConvertibleToInteger(): RoundingNecessaryException
    {
        return new self('This rational number cannot be represented as an integer without rounding.');
    }

    /**
     * @pure
     */
    public static function rationalNotConvertibleToDecimal(): RoundingNecessaryException
    {
        return new self('This rational number has a non-terminating decimal expansion and cannot be represented as a decimal without rounding.');
    }
}
