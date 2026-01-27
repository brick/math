<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

/**
 * Exception thrown when an invalid argument is provided.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements MathException
{
}
