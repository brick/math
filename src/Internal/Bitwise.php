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
        $x2 = $x->abs()->toBase(2);
        $y2 = $y->abs()->toBase(2);

        // prefix a 0 to ensure than when applying the two's complement, length in bits will remain the same
        $x2 = '0' . $x2;
        $y2 = '0' . $y2;

        $lx = strlen($x2);
        $ly = strlen($y2);

        $length = max($lx, $ly);

        if ($lx < $length) {
            $x2 = str_repeat('0', $length - strlen($x2)) . $x2;
        }

        if ($ly < $length) {
            $y2 = str_repeat('0', $length - strlen($y2)) . $y2;
        }

        if ($x->isNegative()) {
            $x2 = self::twosComplement($x2);
        }

        if ($y->isNegative()) {
            $y2 = self::twosComplement($y2);
        }

        $value = '';

        for ($i = 0; $i < $length; $i++) {
            $bx = (int) $x2[$i];
            $by = (int) $y2[$i];

            switch ($operator) {
                case 'and':
                    $value .= (string) ($bx & $by);
                    break;

                case 'or':
                    $value .= (string) ($bx | $by);
                    break;

                case 'xor':
                    $value .= (string) ($bx ^ $by);
                    break;
            }
        }

        if ($value[0] === '1') {
            return BigInteger::parse(self::twosComplement($value), 2)->negated();
        }

        return BigInteger::parse($value, 2);
    }

    /**
     * @param string $number
     *
     * @return string
     */
    private static function twosComplement(string $number) : string
    {
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $number[$i] = ($number[$i] === '0' ? '1' : '0');
        }

        $result = BigInteger::parse($number, 2)->plus(1)->toBase(2);

        return $result;
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
