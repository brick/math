<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when the current PHP platform does not support a required feature.
 */
final class UnsupportedPlatformException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function require64BitPhp(): self
    {
        return new self('This feature requires 64-bit PHP.');
    }

    /**
     * @pure
     */
    public static function unsupportedFloatFormat(): self
    {
        return new self('Unsupported float format: expected IEEE-754 double.');
    }
}
