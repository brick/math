<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;

/**
 * Calculator implementation using only native PHP code.
 *
 * @internal
 */
class NativeCalculator extends Calculator
{
    /**
     * The max number of digits the platform can natively add, subtract, multiply or divide without overflow.
     * For multiplication, this represents the max sum of the lengths of both operands.
     *
     * For addition, it is assumed that an extra digit can hold a carry (1) without overflowing.
     * Example: 32-bit: max number 1,999,999,999 (9 digits + carry)
     *          64-bit: max number 1,999,999,999,999,999,999 (18 digits + carry)
     *
     * @var int
     */
    private $maxDigits;

    /**
     * Class constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        switch (PHP_INT_SIZE) {
            case 4:
                $this->maxDigits = 9;
                break;

            case 8:
                $this->maxDigits = 18;
                break;

            default:
                throw new \RuntimeException('The platform is not 32-bit or 64-bit as expected.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $a, string $b) : string
    {
        if ($a === '0') {
            return $b;
        }

        if ($b === '0') {
            return $a;
        }

        $this->init($a, $b, $aDig, $bDig, $aNeg, $bNeg, $aLen, $bLen);

        if ($aLen <= $this->maxDigits && $bLen <= $this->maxDigits) {
            return (string) ((int) $a + (int) $b);
        }

        if ($aNeg === $bNeg) {
            $result = $this->doAdd($aDig, $bDig, $aLen, $bLen);
        } else {
            $result = $this->doSub($aDig, $bDig, $aLen, $bLen);
        }

        if ($aNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sub(string $a, string $b) : string
    {
        return $this->add($a, $this->neg($b));
    }

    /**
     * {@inheritdoc}
     */
    public function mul(string $a, string $b) : string
    {
        if ($a === '0' || $b === '0') {
            return '0';
        }

        if ($a === '1') {
            return $b;
        }

        if ($b === '1') {
            return $a;
        }

        if ($a === '-1') {
            return $this->neg($b);
        }

        if ($b === '-1') {
            return $this->neg($a);
        }

        $this->init($a, $b, $aDig, $bDig, $aNeg, $bNeg, $aLen, $bLen);

        if ($aLen + $bLen <= $this->maxDigits) {
            return (string) ((int) $a * (int) $b);
        }

        $result = $this->doMul($aDig, $bDig, $aLen, $bLen);

        if ($aNeg !== $bNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function divQ(string $a, string $b) : string
    {
        return $this->divQR($a, $b)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function divR(string $a, string $b): string
    {
        return $this->divQR($a, $b)[1];
    }

    /**
     * {@inheritdoc}
     */
    public function divQR(string $a, string $b) : array
    {
        if ($a === '0') {
            return ['0', '0'];
        }

        if ($a === $b) {
            return ['1', '0'];
        }

        if ($b === '1') {
            return [$a, '0'];
        }

        if ($b === '-1') {
            return [$this->neg($a), '0'];
        }

        $this->init($a, $b, $aDig, $bDig, $aNeg, $bNeg, $aLen, $bLen);

        if ($aLen <= $this->maxDigits && $bLen <= $this->maxDigits) {
            $a = (int) $a;
            $b = (int) $b;

            $r = $a % $b;
            $q = ($a - $r) / $b;

            $q = (string) $q;
            $r = (string) $r;

            return [$q, $r];
        }

        [$q, $r] = $this->doDiv($aDig, $bDig, $aLen, $bLen);

        if ($aNeg !== $bNeg) {
            $q = $this->neg($q);
        }

        if ($aNeg) {
            $r = $this->neg($r);
        }

        return [$q, $r];
    }

    /**
     * {@inheritdoc}
     */
    public function pow(string $a, int $e) : string
    {
        if ($e === 0) {
            return '1';
        }

        if ($e === 1) {
            return $a;
        }

        $odd = $e % 2;
        $e -= $odd;

        $aa = $this->mul($a, $a);
        $result = $this->pow($aa, $e / 2);

        if ($odd === 1) {
            $result = $this->mul($result, $a);
        }

        return $result;
    }

    /**
     * Adapted from https://cp-algorithms.com/num_methods/roots_newton.html
     *
     * {@inheritDoc}
     */
    public function sqrt(string $n) : string
    {
        // initial approximation
        $x = \str_repeat('9', \intdiv(\strlen($n), 2) ?: 1);

        $decreased = false;

        for (;;) {
            $nx = $this->divQ($this->add($x, $this->divQ($n, $x)), '2');

            if ($x === $nx || $this->cmp($nx, $x) > 0 && $decreased) {
                break;
            }

            $decreased = $this->cmp($nx, $x) < 0;
            $x = $nx;
        }

        return $x;
    }

    /**
     * Performs the addition of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return string
     */
    private function doAdd(string $a, string $b, int $x, int $y) : string
    {
        $length = $this->pad($a, $b, $x, $y);

        $carry = 0;
        $result = '';

        for ($i = $length - $this->maxDigits;; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                $i = 0;
            }

            $blockA = \substr($a, $i, $blockLength);
            $blockB = \substr($b, $i, $blockLength);

            $sum = (string) ((int) $blockA + (int) $blockB + $carry);
            $sumLength = \strlen($sum);

            if ($sumLength > $blockLength) {
                $sum = \substr($sum, 1);
                $carry = 1;
            } else {
                if ($sumLength < $blockLength) {
                    $sum = \str_repeat('0', $blockLength - $sumLength) . $sum;
                }
                $carry = 0;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        if ($carry === 1) {
            $result = '1' . $result;
        }

        return $result;
    }

    /**
     * Performs the subtraction of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return string
     */
    private function doSub(string $a, string $b, int $x, int $y) : string
    {
        if ($a === $b) {
            return '0';
        }

        // Ensure that we always subtract to a positive result: biggest minus smallest.
        $cmp = $this->doCmp($a, $b, $x, $y);

        $invert = ($cmp === -1);

        if ($invert) {
            $c = $a;
            $a = $b;
            $b = $c;

            $z = $x;
            $x = $y;
            $y = $z;
        }

        $length = $this->pad($a, $b, $x, $y);

        $carry = 0;
        $result = '';

        $complement = 10 ** $this->maxDigits;

        for ($i = $length - $this->maxDigits;; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                $i = 0;
            }

            $blockA = \substr($a, $i, $blockLength);
            $blockB = \substr($b, $i, $blockLength);

            $sum = (int) $blockA - (int) $blockB - $carry;

            if ($sum < 0) {
                $sum += $complement;
                $carry = 1;
            } else {
                $carry = 0;
            }

            $sum = (string) $sum;
            $sumLength = \strlen($sum);

            if ($sumLength < $blockLength) {
                $sum = \str_repeat('0', $blockLength - $sumLength) . $sum;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        // Carry cannot be 1 when the loop ends, as a > b
        assert($carry === 0);

        $result = \ltrim($result, '0');

        if ($invert) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * Performs the multiplication of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return string
     */
    private function doMul(string $a, string $b, int $x, int $y) : string
    {
        $maxDigits = \intdiv($this->maxDigits, 2);
        $complement = 10 ** $maxDigits;

        $result = '0';

        for ($i = $x - $maxDigits;; $i -= $maxDigits) {
            $blockALength = $maxDigits;

            if ($i < 0) {
                $blockALength += $i;
                $i = 0;
            }

            $blockA = (int) \substr($a, $i, $blockALength);

            $line = '';
            $carry = 0;

            for ($j = $y - $maxDigits;; $j -= $maxDigits) {
                $blockBLength = $maxDigits;

                if ($j < 0) {
                    $blockBLength += $j;
                    $j = 0;
                }

                $blockB = (int) \substr($b, $j, $blockBLength);

                $mul = $blockA * $blockB + $carry;
                $value = $mul % $complement;
                $carry = ($mul - $value) / $complement;

                $value = (string) $value;
                $value = \str_pad($value, $maxDigits, '0', STR_PAD_LEFT);

                $line = $value . $line;

                if ($j === 0) {
                    break;
                }
            }

            if ($carry !== 0) {
                $line = $carry . $line;
            }

            $line = \ltrim($line, '0');

            if ($line !== '') {
                $line .= \str_repeat('0', $x - $blockALength - $i);
                $result = $this->add($result, $line);
            }

            if ($i === 0) {
                break;
            }
        }

        return $result;
    }

    /**
     * Performs the division of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return string[] The quotient and remainder.
     */
    private function doDiv(string $a, string $b, int $x, int $y) : array
    {
        $cmp = $this->doCmp($a, $b, $x, $y);

        if ($cmp === -1) {
            return ['0', $a];
        }

        // we now know that a > b && x >= y

        $q = '0'; // quotient
        $r = $a; // remainder
        $z = $y; // focus length, always $y or $y+1

        for (;;) {
            $focus = \substr($a, 0, $z);

            $cmp = $this->doCmp($focus, $b, $z, $y);

            if ($cmp === -1) {
                if ($z === $x) { // remainder < dividend
                    break;
                }

                $z++;
            }

            $zeros = \str_repeat('0', $x - $z);

            $q = $this->add($q, '1' . $zeros);
            $a = $this->sub($a, $b . $zeros);

            $r = $a;

            if ($r === '0') { // remainder == 0
                break;
            }

            $x = \strlen($a);

            if ($x < $y) { // remainder < dividend
                break;
            }

            $z = $y;
        }

        return [$q, $r];
    }

    /**
     * Compares two non-signed large numbers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return int [-1, 0, 1]
     */
    private function doCmp(string $a, string $b, int $x, int $y) : int
    {
        if ($x > $y) {
            return 1;
        }
        if ($x < $y) {
            return -1;
        }

        for ($i = 0; $i < $x; $i++) {
            $ai = (int) $a[$i];
            $bi = (int) $b[$i];

            if ($ai > $bi) {
                return 1;
            }
            if ($ai < $bi) {
                return -1;
            }
        }

        return 0;
    }

    /**
     * Pads the left of one of the given numbers with zeros if necessary to make both numbers the same length.
     *
     * The numbers must only consist of digits, without leading minus sign.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return int The length of both strings.
     */
    private function pad(string & $a, string & $b, int $x, int $y) : int
    {
        if ($x === $y) {
            return $x;
        }

        if ($x < $y) {
            $a = \str_repeat('0', $y - $x) . $a;

            return $y;
        }

        $b = \str_repeat('0', $x - $y) . $b;

        return $x;
    }
}
