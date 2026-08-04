<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

/**
 * Exception thrown when the current PHP platform does not support a required feature.
 *
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
final class PlatformException extends UnsupportedPlatformException
{
    /**
     * @internal
     *
     * @pure
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function unsupportedFloatFormat(): self
    {
        return new self('Unsupported float format: expected IEEE-754 double.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function pcreFailure(): self
    {
        return new self(sprintf(
            'PCRE regular expression matching failed: %s. Check the pcre.* ini settings.',
            preg_last_error_msg(),
        ));
    }
}
