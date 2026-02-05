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
