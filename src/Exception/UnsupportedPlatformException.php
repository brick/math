<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when the current PHP platform does not support a required feature.
 *
 * @deprecated Catch PlatformException instead.
 *
 * @final
 */
class UnsupportedPlatformException extends RuntimeException implements MathException
{
}
