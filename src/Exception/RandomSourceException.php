<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;
use Throwable;

use function get_debug_type;
use function sprintf;

/**
 * Exception thrown when random byte generation fails.
 */
final class RandomSourceException extends RuntimeException implements MathException
{
    /**
     * @pure
     */
    public static function randomSourceFailure(Throwable $previous): RandomSourceException
    {
        return new self('Random byte generation failed.', 0, $previous);
    }

    /**
     * @pure
     */
    public static function invalidRandomBytesType(mixed $value): RandomSourceException
    {
        return new self(sprintf(
            'The random bytes generator must return a string, got %s.',
            get_debug_type($value),
        ));
    }

    /**
     * @pure
     */
    public static function invalidRandomBytesLength(int $expectedLength, int $actualLength): RandomSourceException
    {
        return new self(sprintf(
            'The random bytes generator returned %d byte(s), expected %d.',
            $actualLength,
            $expectedLength,
        ));
    }
}
