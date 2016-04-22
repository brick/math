<?php

namespace Brick\Math\Exception;

/**
 * Exception thrown when a number cannot be represented at the requested scale without rounding.
 */
class RoundingNecessaryException extends ArithmeticException
{
    /**
     * @return RoundingNecessaryException
     */
    public static function roundingNecessary()
    {
        return new self('Rounding is necessary to represent the result of the operation at this scale.');
    }
}
