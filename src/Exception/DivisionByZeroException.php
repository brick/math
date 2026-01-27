<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a division by zero occurs.
 */
final class DivisionByZeroException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function divisionByZero(): DivisionByZeroException
    {
        return new self('Division by zero.');
    }

    /**
     * @pure
     */
    public static function modulusMustNotBeZero(): DivisionByZeroException
    {
        return new self('The modulus must not be zero.');
    }

    /**
     * @pure
     */
    public static function denominatorMustNotBeZero(): DivisionByZeroException
    {
        return new self('The denominator of a rational number cannot be zero.');
    }
}
