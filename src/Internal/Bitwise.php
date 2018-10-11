<?php

declare(strict_types=1);

namespace Brick\Math\Internal;

use Brick\Math\BigInteger;

/**
 * A helper class to support bitwise operations on BigInteger
 *
 * @internal
 */
abstract class Bitwise
{
    /**
     * Performs a bitwise operation on a BigInteger number.
     *
     * @param BigInteger $x        The base number to operate on.
     * @param BigInteger $y        The second operand.
     * @param string     $operator The operator to use, must be "and", "or" or "xor".
     *
     * @return BigInteger
     */
    public static function bitwise(BigInteger $x, BigInteger $y, string $operator) : BigInteger
    {
        $binaryX = self::toBinary($x);
        $binaryY = self::toBinary($y);

        $maxLength = max(strlen($binaryX), strlen($binaryY));
        $binaryX = $binaryX[0] . str_pad(substr($binaryX, 1), $maxLength - 1, "\x0", STR_PAD_LEFT);
        $binaryY = $binaryY[0] . str_pad(substr($binaryY, 1), $maxLength - 1, "\x0", STR_PAD_LEFT);

        $value = '';

        for ($i = 0; $i < $maxLength; ++$i) {
            switch ($operator) {
                case 'and':
                    $value .= $binaryX[$i] & $binaryY[$i];
                    break;

                case 'or':
                    $value .= $binaryX[$i] | $binaryY[$i];
                    break;

                case 'xor':
                    $value .= $binaryX[$i] ^ $binaryY[$i];
                    break;
            }
        }

        return self::toBigInteger($value);
    }

    /**
     * Returns the binary format of a number.
     *
     * @param BigInteger $x The number to convert to binary format.
     *
     * @return string
     */
    private static function toBinary(BigInteger $x) : string
    {
        $isNegative = $x->isNegative();
        $abs = $x->abs();
        $base = BigInteger::of(256);
        $bytes = '';
        $lastByte = true;

        while ($abs->isGreaterThanOrEqualTo($base)) {
            list($abs, $rest) = $abs->quotientAndRemainder($base);
            $bytes = chr(
                $isNegative ?
                    ($lastByte ? 256 : 255) - $rest->toInt()
                    : $rest->toInt()
            ) . $bytes;
            $lastByte = false;
        }

        $bytes = chr(
            $isNegative ?
                ($lastByte ? 256 : 255) - $abs->toInt()
                : $abs->toInt()
        ) . $bytes;
        $bytes = ($isNegative ? "\xff" : "\x0") . $bytes;

        return $bytes;
    }

    /**
     * Returns the BigInteger representation of a binary number.
     *
     * @param string $bytes The bytes representing the number.
     *
     * @return BigInteger
     */
    private static function toBigInteger(string $bytes) : BigInteger
    {
        $isNegative = $bytes[0] === "\xff";
        $base = BigInteger::of(256);
        $length = strlen($bytes);
        $value = BigInteger::zero();

        for ($i = 1; $i < $length; ++$i) {
            $lastByte = ($i === $length - 1);
            $multiplier = $base->power($length - $i - 1);
            $value = $value->plus(
                BigInteger::of(
                    $isNegative
                        ? ($lastByte ? 256 : 255) - ord($bytes[$i])
                        : ord($bytes[$i])
                )->multipliedBy($multiplier)
            );
        }

        return $isNegative ? $value->negated() : $value;
    }
}
