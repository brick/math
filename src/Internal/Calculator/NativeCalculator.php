<?php

declare(strict_types=1);

namespace Brick\Math\Internal\Calculator;

use Brick\Math\Internal\Calculator;
use Override;

use function array_reverse;
use function assert;
use function implode;
use function intdiv;
use function is_int;
use function ltrim;
use function max;
use function min;
use function sqrt;
use function str_pad;
use function str_repeat;
use function strcmp;
use function strlen;
use function substr;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * Calculator implementation using only native PHP code.
 *
 * @internal
 */
final readonly class NativeCalculator extends Calculator
{
    /**
     * The max number of digits the platform can natively add, subtract, multiply or divide without overflow.
     * For multiplication, this represents the max sum of the lengths of both operands.
     *
     * In addition, it is assumed that an extra digit can hold a carry (1) without overflowing.
     * Example: 32-bit: max number 1,999,999,999 (9 digits + carry)
     *          64-bit: max number 1,999,999,999,999,999,999 (18 digits + carry)
     */
    private int $maxDigits;

    /**
     * @pure
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->maxDigits = match (PHP_INT_SIZE) {
            4 => 9,
            8 => 18,
        };
    }

    #[Override]
    public function add(string $a, string $b): string
    {
        /**
         * @var numeric-string $a
         * @var numeric-string $b
         */
        $result = $a + $b;

        if (is_int($result)) {
            return (string) $result;
        }

        if ($a === '0') {
            return $b;
        }

        if ($b === '0') {
            return $a;
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        $result = $aNeg === $bNeg ? $this->doAdd($aDig, $bDig) : $this->doSub($aDig, $bDig);

        if ($aNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    #[Override]
    public function sub(string $a, string $b): string
    {
        return $this->add($a, $this->neg($b));
    }

    #[Override]
    public function mul(string $a, string $b): string
    {
        /**
         * @var numeric-string $a
         * @var numeric-string $b
         */
        $result = $a * $b;

        if (is_int($result)) {
            return (string) $result;
        }

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

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        $result = $this->doMul($aDig, $bDig);

        if ($aNeg !== $bNeg) {
            $result = $this->neg($result);
        }

        return $result;
    }

    #[Override]
    public function divQ(string $a, string $b): string
    {
        return $this->divQR($a, $b)[0];
    }

    #[Override]
    public function divR(string $a, string $b): string
    {
        return $this->divQR($a, $b)[1];
    }

    #[Override]
    public function divQR(string $a, string $b): array
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

        /** @var numeric-string $a */
        $na = $a * 1; // cast to number

        if (is_int($na)) {
            /** @var numeric-string $b */
            $nb = $b * 1;

            if (is_int($nb)) {
                // the only division that may overflow is PHP_INT_MIN / -1,
                // which cannot happen here as we've already handled a divisor of -1 above.
                $q = intdiv($na, $nb);
                $r = $na % $nb;

                return [
                    (string) $q,
                    (string) $r,
                ];
            }
        }

        [$aNeg, $bNeg, $aDig, $bDig] = $this->init($a, $b);

        [$q, $r] = $this->doDiv($aDig, $bDig);

        if ($aNeg !== $bNeg) {
            $q = $this->neg($q);
        }

        if ($aNeg) {
            $r = $this->neg($r);
        }

        return [$q, $r];
    }

    #[Override]
    public function pow(string $a, int $e): string
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
     * Algorithm from: https://www.geeksforgeeks.org/modular-exponentiation-power-in-modular-arithmetic/.
     */
    #[Override]
    public function modPow(string $base, string $exp, string $mod): string
    {
        // special case: the algorithm below fails with power 0 mod 1 (returns 1 instead of 0)
        if ($mod === '1') {
            return '0';
        }

        if ($exp === '0') {
            return '1';
        }

        $x = $base;

        $res = '1';

        // numbers are positive, so we can use remainder instead of modulo
        $x = $this->divR($x, $mod);

        while ($exp !== '0') {
            // Check if exp is odd using last digit (faster than in_array)
            $lastDigit = $exp[-1];
            if ($lastDigit === '1' || $lastDigit === '3' || $lastDigit === '5' || $lastDigit === '7' || $lastDigit === '9') {
                $res = $this->divR($this->mul($res, $x), $mod);
            }

            $exp = $this->divQ($exp, '2');
            $x = $this->divR($this->mul($x, $x), $mod);
        }

        return $res;
    }

    /**
     * Adapted from https://cp-algorithms.com/num_methods/roots_newton.html.
     */
    #[Override]
    public function sqrt(string $n): string
    {
        if ($n === '0') {
            return '0';
        }


        if ($n === '1') {
            return '1';
        }

        $len = strlen($n);

        // Better initial approximation based on number of digits
        // sqrt(10^k) ≈ 3.16 * 10^(k/2), so for a number with len digits,
        // the sqrt has approximately ceil(len/2) digits
        // We use the leading digits to get a better starting point
        $sqrtLen = intdiv($len + 1, 2);

        // Get leading digits for better approximation
        $leadDigits = (int) substr($n, 0, min(2, $len));
        if ($len % 2 === 0) {
            // Even length: sqrt of 2-digit prefix
            $leadSqrt = (int) sqrt($leadDigits);
        } else {
            // Odd length: sqrt of 1-digit prefix
            $leadSqrt = (int) sqrt($leadDigits);
        }

        // Construct initial approximation
        if ($leadSqrt < 1) {
            $leadSqrt = 1;
        }
        $x = $leadSqrt . str_repeat('0', $sqrtLen - 1);

        $decreased = false;

        for (; ;) {
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
     * @pure
     */
    private function doAdd(string $a, string $b): string
    {
        [$a, $b, $length] = $this->pad($a, $b);

        $carry = 0;
        $chunks = [];

        for ($i = $length - $this->maxDigits; ; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                $i = 0;
            }

            /** @var numeric-string $blockA */
            $blockA = substr($a, $i, $blockLength);

            /** @var numeric-string $blockB */
            $blockB = substr($b, $i, $blockLength);

            $sum = (string) ($blockA + $blockB + $carry);
            $sumLength = strlen($sum);

            if ($sumLength > $blockLength) {
                $sum = substr($sum, 1);
                $carry = 1;
            } else {
                if ($sumLength < $blockLength) {
                    $sum = str_pad($sum, $blockLength, '0', STR_PAD_LEFT);
                }
                $carry = 0;
            }

            $chunks[] = $sum;

            if ($i === 0) {
                break;
            }
        }

        $result = implode('', array_reverse($chunks));

        if ($carry === 1) {
            $result = '1' . $result;
        }

        return $result;
    }

    /**
     * Performs the subtraction of two non-signed large integers.
     *
     * @pure
     */
    private function doSub(string $a, string $b): string
    {
        if ($a === $b) {
            return '0';
        }

        // Ensure that we always subtract to a positive result: biggest minus smallest.
        $cmp = $this->doCmp($a, $b);

        $invert = ($cmp === -1);

        if ($invert) {
            $c = $a;
            $a = $b;
            $b = $c;
        }

        [$a, $b, $length] = $this->pad($a, $b);

        $carry = 0;
        $chunks = [];

        $complement = 10 ** $this->maxDigits;

        for ($i = $length - $this->maxDigits; ; $i -= $this->maxDigits) {
            $blockLength = $this->maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                $i = 0;
            }

            /** @var numeric-string $blockA */
            $blockA = substr($a, $i, $blockLength);

            /** @var numeric-string $blockB */
            $blockB = substr($b, $i, $blockLength);

            $sum = $blockA - $blockB - $carry;

            if ($sum < 0) {
                $sum += $complement;
                $carry = 1;
            } else {
                $carry = 0;
            }

            $sum = (string) $sum;
            $sumLength = strlen($sum);

            if ($sumLength < $blockLength) {
                $sum = str_pad($sum, $blockLength, '0', STR_PAD_LEFT);
            }

            $chunks[] = $sum;

            if ($i === 0) {
                break;
            }
        }

        // Carry cannot be 1 when the loop ends, as a > b
        assert($carry === 0);

        $result = ltrim(implode('', array_reverse($chunks)), '0') ?: '0';

        if ($invert) {
            $result = $this->neg($result);
        }

        return $result;
    }

    /**
     * Performs the multiplication of two non-signed large integers.
     *
     * Uses Karatsuba algorithm for large numbers, which is O(n^1.585) vs O(n²) for grade-school.
     *
     * @pure
     */
    private function doMul(string $a, string $b): string
    {
        $x = strlen($a);
        $y = strlen($b);

        // Karatsuba threshold: use grade-school for small numbers
        // The threshold is tuned for PHP performance - Karatsuba has high overhead
        // so only use it for truly large numbers where O(n^1.585) beats O(n²) + overhead
        if ($x < 150 || $y < 150) {
            return $this->doMulSchool($a, $b);
        }

        return $this->doMulKaratsuba($a, $b);
    }

    /**
     * Grade-school multiplication algorithm for smaller numbers.
     *
     * @pure
     */
    private function doMulSchool(string $a, string $b): string
    {
        $x = strlen($a);
        $y = strlen($b);

        $maxDigits = intdiv($this->maxDigits, 2);
        $complement = 10 ** $maxDigits;

        $result = '0';

        for ($i = $x - $maxDigits; ; $i -= $maxDigits) {
            $blockALength = $maxDigits;

            if ($i < 0) {
                $blockALength += $i;
                $i = 0;
            }

            $blockA = (int) substr($a, $i, $blockALength);

            $lineChunks = [];
            $carry = 0;

            for ($j = $y - $maxDigits; ; $j -= $maxDigits) {
                $blockBLength = $maxDigits;

                if ($j < 0) {
                    $blockBLength += $j;
                    $j = 0;
                }

                $blockB = (int) substr($b, $j, $blockBLength);

                $mul = $blockA * $blockB + $carry;
                $value = $mul % $complement;
                $carry = (int) (($mul - $value) / $complement);

                $lineChunks[] = str_pad((string) $value, $maxDigits, '0', STR_PAD_LEFT);

                if ($j === 0) {
                    break;
                }
            }

            $line = implode('', array_reverse($lineChunks));

            if ($carry !== 0) {
                $line = $carry . $line;
            }

            $line = ltrim($line, '0');

            if ($line !== '') {
                $line .= str_repeat('0', $x - $blockALength - $i);
                $result = $this->add($result, $line);
            }

            if ($i === 0) {
                break;
            }
        }

        return $result;
    }

    /**
     * Karatsuba multiplication algorithm for large numbers.
     *
     * For numbers with n digits, this runs in O(n^1.585) time instead of O(n²).
     *
     * @pure
     */
    private function doMulKaratsuba(string $a, string $b): string
    {
        $x = strlen($a);
        $y = strlen($b);

        // Base case: use grade-school for small numbers
        if ($x < 150 || $y < 150) {
            return $this->doMulSchool($a, $b);
        }

        // Make both numbers the same length by padding
        $m = max($x, $y);
        $m2 = intdiv($m + 1, 2);

        // Pad to equal length
        if ($x < $m) {
            $a = str_repeat('0', $m - $x) . $a;
        }
        if ($y < $m) {
            $b = str_repeat('0', $m - $y) . $b;
        }

        // Split numbers: a = a1 * 10^m2 + a0, b = b1 * 10^m2 + b0
        $split = $m - $m2;
        $a1 = ltrim(substr($a, 0, $split), '0') ?: '0';
        $a0 = ltrim(substr($a, $split), '0') ?: '0';
        $b1 = ltrim(substr($b, 0, $split), '0') ?: '0';
        $b0 = ltrim(substr($b, $split), '0') ?: '0';

        // Karatsuba's three multiplications
        $z2 = $this->doMul($a1, $b1); // a1 * b1
        $z0 = $this->doMul($a0, $b0); // a0 * b0

        // z1 = (a1 + a0)(b1 + b0) - z2 - z0 = a1*b0 + a0*b1
        $a1a0 = $this->doAdd($a1, $a0);
        $b1b0 = $this->doAdd($b1, $b0);
        $z1 = $this->doMul($a1a0, $b1b0);
        $z1 = $this->doSub($z1, $z2);
        $z1 = $this->doSub($z1, $z0);

        // Result = z2 * 10^(2*m2) + z1 * 10^m2 + z0
        if ($z2 !== '0') {
            $z2 .= str_repeat('0', 2 * $m2);
        }
        if ($z1 !== '0') {
            $z1 .= str_repeat('0', $m2);
        }

        $result = $this->doAdd($z2, $z1);
        $result = $this->doAdd($result, $z0);

        return $result;
    }

    /**
     * Performs the division of two non-signed large integers.
     *
     * @return string[] The quotient and remainder.
     *
     * @pure
     */
    private function doDiv(string $a, string $b): array
    {
        $cmp = $this->doCmp($a, $b);

        if ($cmp === -1) {
            return ['0', $a];
        }

        if ($cmp === 0) {
            return ['1', '0'];
        }

        $x = strlen($a);
        $y = strlen($b);

        // we now know that a > b && x >= y

        /** @var numeric-string $b */
        $nb = $b + 0; // cast to number (int or float depending on size)

        // Fast path: when divisor fits in an int and won't overflow during long division
        /** @phpstan-ignore function.alreadyNarrowedType */
        if (is_int($nb) && is_int(($nb - 1) * 10 + 9)) {
            $q = '0';
            $r = (int) substr($a, 0, $y - 1);

            for ($i = $y - 1; $i < $x; $i++) {
                $n = $r * 10 + (int) $a[$i];
                /** @var int $nb */
                $q .= intdiv($n, $nb);
                $r = $n % $nb;
            }

            return [ltrim($q, '0') ?: '0', (string) $r];
        }

        // Proper long division algorithm for large divisors
        // We process the dividend digit by digit, estimating quotient digits
        return $this->doLongDiv($a, $b, $x, $y);
    }

    /**
     * Performs long division for large numbers.
     *
     * Uses digit-by-digit estimation with correction.
     *
     * @return array{string, string} The quotient and remainder.
     *
     * @pure
     */
    private function doLongDiv(string $a, string $b, int $x, int $y): array
    {
        $q = '';
        $r = '';
        $rLen = 0;

        for ($i = 0; $i < $x; $i++) {
            // Bring down next digit
            if ($rLen === 0 || $r === '0') {
                $r = $a[$i];
                $rLen = ($r === '0') ? 0 : 1;
            } else {
                $r .= $a[$i];
                $rLen++;
            }

            // If remainder < divisor (by length), quotient digit is 0
            if ($rLen < $y) {
                $q .= '0';

                continue;
            }

            // Compare remainder with divisor
            if ($rLen === $y) {
                $cmp = strcmp($r, $b) <=> 0;
                if ($cmp < 0) {
                    $q .= '0';

                    continue;
                }
                if ($cmp === 0) {
                    $q .= '1';
                    $r = '0';
                    $rLen = 0;

                    continue;
                }
                // cmp > 0, fall through to compute quotient digit
            }

            // Compute quotient digit using native division on leading digits
            // We use up to 15 digits which fit safely in a 64-bit int
            $useDigits = min(15, $rLen);
            $rTop = (int) substr($r, 0, $useDigits);
            $bTop = (int) substr($b, 0, min(15, $y));

            // Scale based on digit difference
            $digitDiff = $rLen - $y;
            if ($digitDiff > 0) {
                // Adjust bTop for the digit difference
                $adjustDigits = min($digitDiff, 15 - strlen((string) $bTop));
                if ($adjustDigits > 0) {
                    $bTopAdjusted = $bTop * (10 ** $adjustDigits);
                } else {
                    $bTopAdjusted = $bTop;
                }
                $qDigit = min(9, (int) ($rTop / max(1, $bTopAdjusted)));
            } else {
                $qDigit = min(9, (int) ($rTop / max(1, $bTop)));
            }

            // Compute qDigit * b and compare/subtract
            if ($qDigit > 0) {
                $product = $this->mulSmall($b, $qDigit);
                $pLen = strlen($product);

                // Quick comparison by length
                if ($pLen > $rLen) {
                    // Product too big, reduce qDigit
                    $qDigit--;
                    if ($qDigit > 0) {
                        $product = $this->mulSmall($b, $qDigit);
                        $pLen = strlen($product);
                    }
                }

                if ($qDigit > 0) {
                    // Compare product with r
                    if ($pLen < $rLen || ($pLen === $rLen && strcmp($product, $r) <= 0)) {
                        $r = $this->doSub($r, $product);
                        $r = ltrim($r, '0') ?: '0';
                        $rLen = strlen($r);
                    } else {
                        // Still too big
                        $qDigit--;
                        if ($qDigit > 0) {
                            $product = $this->mulSmall($b, $qDigit);
                            $r = $this->doSub($r, $product);
                            $r = ltrim($r, '0') ?: '0';
                            $rLen = strlen($r);
                        }
                    }
                }
            }

            // Correction: increment if we underestimated
            while ($rLen > $y || ($rLen === $y && strcmp($r, $b) >= 0)) {
                $r = $this->doSub($r, $b);
                $r = ltrim($r, '0') ?: '0';
                $rLen = strlen($r);
                $qDigit++;
            }

            $q .= $qDigit;
        }

        return [ltrim($q, '0') ?: '0', ($r === '' || $r === '0') ? '0' : $r];
    }

    /**
     * Multiplies a large number by a small integer (0-9).
     *
     * @pure
     */
    private function mulSmall(string $a, int $m): string
    {
        if ($m === 0) {
            return '0';
        }

        if ($m === 1) {
            return $a;
        }

        $len = strlen($a);
        $result = str_repeat('0', $len + 1);
        $carry = 0;
        $pos = $len;

        for ($i = $len - 1; $i >= 0; $i--) {
            $product = ((int) $a[$i]) * $m + $carry;
            $carry = (int) ($product / 10);
            $result[$pos--] = (string) ($product % 10);
        }

        if ($carry > 0) {
            $result[0] = (string) $carry;

            return $result;
        }

        return substr($result, 1);
    }

    /**
     * Compares two non-signed large numbers.
     *
     * @return -1|0|1
     *
     * @pure
     */
    private function doCmp(string $a, string $b): int
    {
        $x = strlen($a);
        $y = strlen($b);

        $cmp = $x <=> $y;

        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp($a, $b) <=> 0; // enforce -1|0|1
    }

    /**
     * Pads the left of one of the given numbers with zeros if necessary to make both numbers the same length.
     *
     * The numbers must only consist of digits, without leading minus sign.
     *
     * @return array{string, string, int}
     *
     * @pure
     */
    private function pad(string $a, string $b): array
    {
        $x = strlen($a);
        $y = strlen($b);

        if ($x > $y) {
            $b = str_repeat('0', $x - $y) . $b;

            return [$a, $b, $x];
        }

        if ($x < $y) {
            $a = str_repeat('0', $y - $x) . $a;

            return [$a, $b, $y];
        }

        return [$a, $b, $x];
    }
}
