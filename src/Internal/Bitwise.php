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
        $bx = self::toBinary((string) $x->abs());
        $by = self::toBinary((string) $y->abs());

        $lx = strlen($bx);
        $ly = strlen($by);

        if ($lx > $ly) {
            $by = str_repeat("\x00", $lx - $ly) . $by;
        } elseif ($ly > $lx) {
            $bx = str_repeat("\x00", $ly - $lx) . $bx;
        }

        if ($x->isNegative()) {
            $bx = self::twosComplement($bx);
        }
        if ($y->isNegative()) {
            $by = self::twosComplement($by);
        }

        switch ($operator) {
            case 'and':
                $value = $bx & $by;
                $negative = ($x->isNegative() and $y->isNegative());
                break;

            case 'or':
                $value = $bx | $by;
                $negative = ($x->isNegative() or $y->isNegative());
                break;

            case 'xor':
                $value = $bx ^ $by;
                $negative = ($x->isNegative() xor $y->isNegative());
                break;
        }

        if ($negative) {
            $value = self::twosComplement($value);
        }

        $result = self::toBigInteger($value);

        return $negative ? $result->negated() : $result;
    }

    /**
     * @param string $number A positive, binary number.
     *
     * @return string
     */
    private static function twosComplement(string $number) : string
    {
        $xor = str_repeat("\xff", strlen($number));

        $number = $number ^ $xor;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $byte = ord($number[$i]);

            if (++$byte !== 256) {
                $number[$i] = chr($byte);
                break;
            }

            $number[$i] = chr(0);
        }

        return $number;
    }

    /**
     * Converts a decimal number to a binary string.
     *
     * @param string $number The number to convert, positive or zero, only digits.
     *
     * @return string
     */
    private static function toBinary(string $number) : string
    {
        $calculator = Calculator::get();

        $result = '';

        while ($number !== '0') {
            [$number, $remainder] = $calculator->divQR($number, '256');
            $remainder = (int) $remainder;

            $result .= chr($remainder);
        }

        return strrev($result);
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

        $result = '0';
        $power = '1';

        for ($i = strlen($bytes) - 1; $i >= 0; $i--) {
            $index = ord($bytes[$i]);

            if ($index !== 0) {
                $result = $calculator->add($result, ($index === 1)
                    ? $power
                    : $calculator->mul($power, (string) $index)
                );
            }

            if ($i !== 0) {
                $power = $calculator->mul($power, '256');
            }
        }

        return BigInteger::of($result);
    }
}
