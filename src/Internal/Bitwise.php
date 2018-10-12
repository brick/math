<?php

declare(strict_types=1);

namespace Brick\Math\Internal;

use Brick\Math\BigInteger;

/**
 * A helper class to support bitwise operations on BigInteger.
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

        $binaryX = $binaryX[0] . str_pad(substr($binaryX, 1), $maxLength - 1, $binaryX[0], STR_PAD_LEFT);
        $binaryY = $binaryY[0] . str_pad(substr($binaryY, 1), $maxLength - 1, $binaryY[0], STR_PAD_LEFT);
        $value = '';

        switch ($operator) {
            case 'and':
                $value = $binaryX & $binaryY;
                break;

            case 'or':
                $value = $binaryX | $binaryY;
                break;

            case 'xor':
                $value = $binaryX ^ $binaryY;
                break;
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
        $calculator = Calculator::get();
        $isNegative = $x->isNegative();
        $abs = (string) $x->abs();
        $bytes = '';
        $lastByte = true;

        while ($calculator->cmp($abs, '256') >= 0) {
            list($abs, $rest) = $calculator->divQR($abs, '256');
            $bytes = chr(
                $isNegative ?
                    ($lastByte ? 256 : 255) - (int) $rest
                    : (int) $rest
            ) . $bytes;
            $lastByte = false;
        }

        $bytes = chr(
            $isNegative ?
                ($lastByte ? 256 : 255) - ((int) $abs)
                : (int) $abs
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
        $calculator = Calculator::get();
        $isNegative = $bytes[0] === "\xff";
        $length = strlen($bytes);
        $value = '0';

        for ($i = 1; $i < $length; ++$i) {
            $lastByte = ($i === $length - 1);
            $multiplier = $calculator->pow('256', $length - $i - 1);
            $value = $calculator->add(
                $value,
                $calculator->mul(
                    (string) (
                        $isNegative
                        ? ($lastByte ? 256 : 255) - ord($bytes[$i])
                        : ord($bytes[$i])
                    ),
                    $multiplier
                )
            );
        }

        return BigInteger::of($isNegative ? $calculator->neg($value) : $value);
    }
}
