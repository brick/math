<?php

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
     * The max number of digits the platform can natively add, subtract or divide without overflow.
     *
     * @var int
     */
    private $maxDigitsAddDiv = 0;

    /**
     * The max number of digits the platform can natively multiply without overflow.
     *
     * @var int
     */
    private $maxDigitsMul = 0;

    /**
     * Class constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        switch (PHP_INT_SIZE) {
            case 4:
                $this->maxDigitsAddDiv = 9;
                $this->maxDigitsMul = 4;
                break;

            case 8:
                $this->maxDigitsAddDiv = 18;
                $this->maxDigitsMul = 9;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($a, $b)
    {
        if ($a === '0') {
            return $b;
        }

        if ($b === '0') {
            return $a;
        }

        $this->init($a, $b, $aDig, $bDig, $aNeg, $bNeg, $aLen, $bLen);

        if ($aLen <= $this->maxDigitsAddDiv && $bLen <= $this->maxDigitsAddDiv) {
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
    public function sub($a, $b)
    {
        return $this->add($a, $this->neg($b));
    }

    /**
     * {@inheritdoc}
     */
    public function mul($a, $b)
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

        if ($aLen <= $this->maxDigitsMul && $bLen <= $this->maxDigitsMul) {
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
    public function divQ($a, $b)
    {
        return $this->divQR($a, $b)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function divR($a, $b)
    {
        return $this->divQR($a, $b)[1];
    }

    /**
     * {@inheritdoc}
     */
    public function divQR($a, $b)
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

        if ($aLen <= $this->maxDigitsAddDiv && $bLen <= $this->maxDigitsAddDiv) {
            $a = (int) $a;
            $b = (int) $b;

            $r = $a % $b;
            $q = ($a - $r) / $b;

            $q = (string) $q;
            $r = (string) $r;

            return [$q, $r];
        }

        list ($q, $r) = $this->doDiv($aDig, $bDig, $aLen, $bLen);

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
    public function pow($a, $e)
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
     * Performs the addition of two non-signed large integers.
     *
     * @param string $a The first operand.
     * @param string $b The second operand.
     * @param int    $x The length of the first operand.
     * @param int    $y The length of the second operand.
     *
     * @return string
     */
    private function doAdd($a, $b, $x, $y)
    {
        $length = $this->pad($a, $b, $x, $y);

        $carry = 0;
        $result = '';

        for ($i = $length - 1; $i >= 0; $i--) {
            $sum = (int) $a[$i] + (int) $b[$i] + $carry;

            if ($sum >= 10) {
                $carry = 1;
                $sum -= 10;
            } else {
                $carry = 0;
            }

            $result .= $sum;
        }

        if ($carry !== 0) {
            $result .= $carry;
        }

        return strrev($result);
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
    private function doSub($a, $b, $x, $y)
    {
        if ($a === $b) {
            return '0';
        }

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

        for ($i = $length - 1; $i >= 0; $i--) {
            $sum = (int) $a[$i] - (int) $b[$i] - $carry;

            if ($sum < 0) {
                $carry = 1;
                $sum += 10;
            } else {
                $carry = 0;
            }

            $result .= $sum;
        }

        $result = strrev($result);
        $result = ltrim($result, '0');

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
    private function doMul($a, $b, $x, $y)
    {
        $result = '0';

        for ($i = $x - 1; $i >= 0; $i--) {
            $line = str_repeat('0', $x - 1 - $i);
            $carry = 0;
            for ($j = $y - 1; $j >= 0; $j--) {
                $mul = (int) $a[$i] * (int) $b[$j] + $carry;
                $digit = $mul % 10;
                $carry = ($mul - $digit) / 10;
                $line .= $digit;
            }

            if ($carry !== 0) {
                $line .= $carry;
            }

            $line = rtrim($line, '0');

            if ($line !== '') {
                $result = $this->add($result, strrev($line));
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
    private function doDiv($a, $b, $x, $y)
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
            $focus = substr($a, 0, $z);

            $cmp = $this->doCmp($focus, $b, $z, $y);

            if ($cmp === -1) {
                if ($z === $x) { // remainder < dividend
                    break;
                }

                $z++;
            }

            $zeros = str_repeat('0', $x - $z);

            $q = $this->add($q, '1' . $zeros);
            $a = $this->sub($a, $b . $zeros);

            $r = $a;

            if ($r === '0') { // remainder == 0
                break;
            }

            $x = strlen($a);

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
    private function doCmp($a, $b, $x, $y)
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
    private function pad(& $a, & $b, $x, $y)
    {
        $length = $x > $y ? $x : $y;

        if ($x < $length) {
            $a = str_repeat('0', $length - $x) . $a;
        }
        if ($y < $length) {
            $b = str_repeat('0', $length - $y) . $b;
        }

        return $length;
    }
}
