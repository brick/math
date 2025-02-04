<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class BigRational.
 */
class BigRationalTest extends AbstractTestCase
{
    /**
     * @param string     $numerator   The expected numerator.
     * @param string     $denominator The expected denominator.
     * @param int|string $n           The input numerator.
     * @param int|string $d           The input denominator.
     */
    #[DataProvider('providerNd')]
    public function testNd(string $numerator, string $denominator, int|string $n, int|string $d) : void
    {
        $rational = BigRational::nd($n, $d);
        self::assertBigRationalInternalValues($numerator, $denominator, $rational);
    }

    public static function providerNd() : array
    {
        return [
            ['7', '1', '7', 1],
            ['7', '36', 7, 36],
            ['-7', '36', 7, -36],
            ['9', '15', '-9', -15],
            ['-98765432109876543210', '12345678901234567890', '-98765432109876543210', '12345678901234567890'],
        ];
    }

    public function testNdWithZeroDenominator() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigRational::nd(1, 0);
    }

    /**
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     * @param string $string      The string to parse.
     */
    #[DataProvider('providerOf')]
    public function testOf(string $numerator, string $denominator, string $string) : void
    {
        $rational = BigRational::of($string);
        self::assertBigRationalInternalValues($numerator, $denominator, $rational);
    }

    public static function providerOf() : array
    {
        return [
            ['123', '456', '123/456'],
            ['123', '456', '+123/456'],
            ['-2345', '6789', '-2345/6789'],
            ['123456', '1', '123456'],
            ['-1234567', '1', '-1234567'],
            ['0', '123', '-0/123'],
            ['-1234567890987654321012345678909876543210', '9999', '-1234567890987654321012345678909876543210/9999'],
            ['1230000', '1', '123e4'],
            ['1125', '1000', '1.125'],
        ];
    }

    public function testOfWithZeroDenominator() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigRational::of('2/0');
    }

    /**
     * @param string $string An invalid string representation.
     */
    #[DataProvider('providerOfInvalidString')]
    public function testOfInvalidString(string $string) : void
    {
        $this->expectException(NumberFormatException::class);
        BigRational::of($string);
    }

    public static function providerOfInvalidString() : array
    {
        return [
            ['123/-456'],
            ['1e4/2'],
            ['1.2/3'],
            ['1e2/3'],
            [' 1/2'],
            ['1/2 '],
            ['+'],
            ['-'],
            ['/'],
        ];
    }

    public function testZero() : void
    {
        self::assertBigRationalInternalValues('0', '1', BigRational::zero());
        self::assertSame(BigRational::zero(), BigRational::zero());
    }

    public function testOne() : void
    {
        self::assertBigRationalInternalValues('1', '1', BigRational::one());
        self::assertSame(BigRational::one(), BigRational::one());
    }

    public function testTen() : void
    {
        self::assertBigRationalInternalValues('10', '1', BigRational::ten());
        self::assertSame(BigRational::ten(), BigRational::ten());
    }

    public function testAccessors() : void
    {
        $rational = BigRational::nd(123456789, 987654321);

        self::assertBigIntegerEquals('123456789', $rational->getNumerator());
        self::assertBigIntegerEquals('987654321', $rational->getDenominator());
    }

    /**
     * @param array  $values The values to compare.
     * @param string $min    The expected minimum value, in rational form.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $values, string $min) : void
    {
        self::assertBigRationalEquals($min, BigRational::min(... $values));
    }

    public static function providerMin() : array
    {
        return [
            [['1/2', '1/4', '1/3'], '1/4'],
            [['1/2', '0.1', '1/3'], '1/10'],
            [['-0.25', '-0.3', '-1/8', '123456789123456789123456789', 2e25], '-3/10'],
            [['1e30', '123456789123456789123456789/3', 2e26], '123456789123456789123456789/3'],
        ];
    }

    public function testMinOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigRational::min();
    }

    /**
     * @param array  $values The values to compare.
     * @param string $max    The expected maximum value, in rational form.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $values, string $max) : void
    {
        self::assertBigRationalEquals($max, BigRational::max(... $values));
    }

    public static function providerMax() : array
    {
        return [
            [['-5532146515641651651321321064580/32453', '-1/2', '-1/99'], '-1/99'],
            [['1e-30', '123456789123456789123456789/2', 2e25], '123456789123456789123456789/2'],
            [['999/1000', '1'], '1'],
            [[0, 0.9, -1.00], '9/10'],
            [[0, 0.01, -1, -1.2], '1/100'],
            [['1e-30', '15185185062185185062185185047/123', 2e25], '15185185062185185062185185047/123'],
            [['1e-30', '15185185062185185062185185047/123', 2e26], '200000000000000000000000000'],
        ];
    }

    public function testMaxOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigRational::max();
    }

    /**
     * @param array  $values The values to add.
     * @param string $sum    The expected sum, in rational form.
     */
    #[DataProvider('providerSum')]
    public function testSum(array $values, string $sum) : void
    {
        self::assertBigRationalEquals($sum, BigRational::sum(... $values));
    }

    public static function providerSum() : array
    {
        return [
            [['-5532146515641651651321321064580/32453', '-1/2', '-1/99'], '-1095365010097047026961621574064593/6425694'],
            [['1e-30', '123456789123456789123456789/2', 2e25], '163456789123456789123456789000000000000000000000000000002/2000000000000000000000000000000'],
            [['999/1000', '1'], '1999/1000'],
            [[0, 0.9, -1.00], '-1/10'],
            [[0, 0.01, -1, -1.2], '-2190/1000'],
            [['1e-30', '15185185062185185062185185047/123', 2e25], '17645185062185185062185185047000000000000000000000000000123/123000000000000000000000000000000'],
            [['1e-30', '15185185062185185062185185047/123', 2e26], '39785185062185185062185185047000000000000000000000000000123/123000000000000000000000000000000'],
        ];
    }

    public function testSumOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigRational::sum();
    }

    /**
     * @param int|string $rational  The rational number to test.
     * @param string     $quotient  The expected quotient.
     * @param string     $remainder The expected remainder.
     */
    #[DataProvider('providerQuotientAndRemainder')]
    public function testQuotientAndRemainder(int|string $rational, string $quotient, string $remainder) : void
    {
        $rational = BigRational::of($rational);

        self::assertBigIntegerEquals($quotient, $rational->quotient());
        self::assertBigIntegerEquals($remainder, $rational->remainder());

        $quotientAndRemainder = $rational->quotientAndRemainder();

        self::assertBigIntegerEquals($quotient, $quotientAndRemainder[0]);
        self::assertBigIntegerEquals($remainder, $quotientAndRemainder[1]);
    }

    public static function providerQuotientAndRemainder() : array
    {
        return [
            ['1000/3', '333', '1'],
            ['895/400', '2', '95'],
            ['-2.5', '-2', '-5'],
            [-2, '-2', '0'],
        ];
    }

    /**
     * @param string               $rational The rational number to test.
     * @param BigNumber|int|string $plus     The number to add.
     * @param string               $expected The expected rational number result.
     */
    #[DataProvider('providerPlus')]
    public function testPlus(string $rational, BigNumber|int|string $plus, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->plus($plus));
    }

    public static function providerPlus() : array
    {
        return [
            ['123/456', 1, '579/456'],
            ['123/456', BigInteger::of(2), '1035/456'],
            ['123/456', BigRational::nd(2, 3), '1281/1368'],
            ['234/567', '123/28', '76293/15876'],
            ['-1234567890123456789/497', '79394345/109859892', '-135629495075630790047217323/54600366324'],
            ['-1234567890123456789/999', '-98765/43210', '-53345678532234666518925/43166790'],
            ['123/456789123456789123456789', '-987/654321987654321', '-450850864771369260370369260/298887167199121283949604203169112635269'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $minus    The number to subtract.
     * @param string $expected The expected rational number result.
     */
    #[DataProvider('providerMinus')]
    public function testMinus(string $rational, string $minus, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->minus($minus));
    }

    public static function providerMinus() : array
    {
        return [
            ['123/456', '1', '-333/456'],
            ['234/567', '123/28', '-63189/15876'],
            ['-1234567890123456789/497', '79394345/109859892', '-135629495075630868965196253/54600366324'],
            ['-1234567890123456789/999', '-98765/43210', '-53345678532234469186455/43166790'],
            ['123/456789123456789123456789', '-987/654321987654321', '450850864932332469333332226/298887167199121283949604203169112635269'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $minus    The number to multiply.
     * @param string $expected The expected rational number result.
     */
    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(string $rational, string $minus, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->multipliedBy($minus));
    }

    public static function providerMultipliedBy() : array
    {
        return [
            ['123/456', '1', '123/456'],
            ['123/456', '2', '246/456'],
            ['123/456', '1/2', '123/912'],
            ['123/456', '2/3', '246/1368'],
            ['-123/456', '2/3', '-246/1368'],
            ['123/456', '-2/3', '-246/1368'],
            ['489798742123504/387590928349859', '324893948394/23609901123', '159132647246919822550452576/9150983494511948540991657'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $minus    The number to multiply.
     * @param string $expected The expected rational number result.
     */
    #[DataProvider('providerDividedBy')]
    public function testDividedBy(string $rational, string $minus, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->dividedBy($minus));
    }

    public static function providerDividedBy() : array
    {
        return [
            ['123/456', '1', '123/456'],
            ['123/456', '2', '123/912'],
            ['123/456', '1/2', '246/456'],
            ['123/456', '2/3', '369/912'],
            ['-123/456', '2/3', '-369/912'],
            ['123/456', '-2/3', '-369/912'],
            ['489798742123504/387590928349859', '324893948394/23609901123', '11564099871705704494294992/125925947073281641523176446'],
        ];
    }

    /**
     * @param string $number   The base number.
     * @param int    $exponent The exponent to apply.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerPower')]
    public function testPower(string $number, int $exponent, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($number)->power($exponent));
    }

    public static function providerPower() : array
    {
        return [
            ['-3',   0, '1'],
            ['-2/3', 0, '1'],
            ['-1/2', 0, '1'],
            ['0',    0, '1'],
            ['1/3',  0, '1'],
            ['2/3',  0, '1'],
            ['3/2',  0, '1'],

            ['-3/2', 1, '-3/2'],
            ['-2/3', 1, '-2/3'],
            ['-1/3', 1, '-1/3'],
            ['0',    1, '0'],
            ['1/3',  1, '1/3'],
            ['2/3',  1, '2/3'],
            ['3/2',  1, '3/2'],

            ['-3/4', 2, '9/16'],
            ['-2/3', 2, '4/9'],
            ['-1/2', 2, '1/4'],
            ['0',    2, '0'],
            ['1/2',  2, '1/4'],
            ['2/3',  2, '4/9'],
            ['3/4',  2, '9/16'],

            ['-3/4', 3, '-27/64'],
            ['-2/3', 3, '-8/27'],
            ['-1/2', 3, '-1/8'],
            ['0',    3, '0'],
            ['1/2',  3, '1/8'],
            ['2/3',  3, '8/27'],
            ['3/4',  3, '27/64'],

            ['0', 1000000, '0'],
            ['1', 1000000, '1'],

            ['-2/3', 99, '-633825300114114700748351602688/171792506910670443678820376588540424234035840667'],
            ['-2/3', 100, '1267650600228229401496703205376/515377520732011331036461129765621272702107522001'],

            ['-123/33', 25, '-17685925284953355608333258649989090388842388168292443/91801229324973413645775482048441660193'],
            [ '123/33', 26, '2175368810049262739824990813948658117827613744699970489/3029440567724122650310590907598574786369'],

            ['-123456789/2', 8, '53965948844821664748141453212125737955899777414752273389058576481/256'],
            ['9876543210/3', 7, '9167159269868350921847491739460569765344716959834325922131706410000000/2187']
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected reciprocal.
     */
    #[DataProvider('providerReciprocal')]
    public function testReciprocal(string $rational, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->reciprocal());
    }

    public static function providerReciprocal() : array
    {
        return [
            ['1', '1'],
            ['2', '1/2'],
            ['1/2', '2'],
            ['123/456', '456/123'],
            ['-234/567', '-567/234'],
            ['489798742123504998877665/387590928349859112233445', '387590928349859112233445/489798742123504998877665'],
        ];
    }

    public function testReciprocalOfZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigRational::nd(0, 2)->reciprocal();
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected absolute number.
     */
    #[DataProvider('providerAbs')]
    public function testAbs(string $rational, string $expected, bool|Closure $callback = true) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->abs($callback));
    }

    public static function providerAbs() : array
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['-1', '1'],
            ['123/456', '123/456'],
            ['-234/567', '234/567'],
            ['-489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445'],
            ['0', '0', fn () => true],
            ['1', '1', fn () => true],
            ['-1', '1', fn () => true],
            ['123/456', '123/456', fn () => true],
            ['-234/567', '234/567', fn () => true],
            ['-489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445', fn () => true],
            ['0', '0', false],
            ['1', '1', false],
            ['-1', '-1', false],
            ['123/456', '123/456', false],
            ['-234/567', '-234/567', false],
            ['-489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445', false],
            ['0', '0', fn () => false],
            ['1', '1', fn () => false],
            ['-1', '-1', fn () => false],
            ['123/456', '123/456', fn () => false],
            ['-234/567', '-234/567', fn () => false],
            ['-489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445', fn () => false],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected negated number.
     */
    #[DataProvider('providerNegated')]
    public function testNegated(string $rational, string $expected, bool|Closure $callback = true) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->negated($callback));
    }

    public static function providerNegated() : array
    {
        return [
            ['0', '0'],
            ['1', '-1'],
            ['-1', '1'],
            ['123/456', '-123/456'],
            ['-234/567', '234/567'],
            ['-489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445'],
            ['489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445'],
            ['0', '0', fn () => true],
            ['1', '-1', fn () => true],
            ['-1', '1', fn () => true],
            ['123/456', '-123/456', fn () => true],
            ['-234/567', '234/567', fn () => true],
            ['-489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445', fn () => true],
            ['489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445', fn () => true],
            ['0', '0', false],
            ['1', '1', false],
            ['-1', '-1', false],
            ['123/456', '123/456', false],
            ['-234/567', '-234/567', false],
            ['-489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445', false],
            ['489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445', false],
            ['0', '0', fn () => false],
            ['1', '1', fn () => false],
            ['-1', '-1', fn () => false],
            ['123/456', '123/456', fn () => false],
            ['-234/567', '-234/567', fn () => false],
            ['-489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445', fn () => false],
            ['489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445', fn () => false],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected negated number.
     */
    #[DataProvider('providerSimplified')]
    public function testSimplified(string $rational, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->simplified());
    }

    public static function providerSimplified() : array
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['-1', '-1'],
            ['0/123456', '0'],
            ['-0/123456', '0'],
            ['-1/123456', '-1/123456'],
            ['4/6', '2/3'],
            ['-4/6', '-2/3'],
            ['123/456', '41/152'],
            ['-234/567', '-26/63'],
            ['489798742123504998877665/387590928349859112233445', '32653249474900333258511/25839395223323940815563'],
            ['-395651984391591565172038784/445108482440540510818543632', '-8/9'],
            ['1.125', '9/8'],
        ];
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testCompareTo(string $a, int|string $b, int $cmp) : void
    {
        self::assertSame($cmp, BigRational::of($a)->compareTo($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsEqualTo(string $a, int|string $b, int $cmp) : void
    {
        self::assertSame($cmp === 0, BigRational::of($a)->isEqualTo($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThan(string $a, int|string $b, int $cmp) : void
    {
        self::assertSame($cmp < 0, BigRational::of($a)->isLessThan($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThanOrEqualTo(string $a, int|string $b, int $cmp) : void
    {
        self::assertSame($cmp <= 0, BigRational::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThan(string $a, int|string $b, int $cmp) : void
    {
        self::assertSame($cmp > 0, BigRational::of($a)->isGreaterThan($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThanOrEqualTo(string $a, int|string $b, int $cmp) : void
    {
        self::assertSame($cmp >= 0, BigRational::of($a)->isGreaterThanOrEqualTo($b));
    }

    public static function providerCompareTo() : array
    {
        return [
            ['-1', '1/2', -1],
            ['1', '1/2', 1],
            ['1', '-1/2', 1],
            ['-1', '-1/2', -1],
            ['1/2', '1/2', 0],
            ['-1/2', '-1/2', 0],
            ['1/2', '2/4', 0],
            ['1/3', '122/369', 1],
            ['1/3', '123/369', 0],
            ['1/3', '124/369', -1],
            ['1/3', '123/368', -1],
            ['1/3', '123/370', 1],
            ['-1/3', '-122/369', -1],
            ['-1/3', '-123/369', 0],
            ['-1/3', '-124/369', 1],
            ['-1/3', '-123/368', 1],
            ['-1/3', '-123/370', -1],
            ['999999999999999999999999999999/1000000000000000000000000000000', '1', -1],
            ['1', '999999999999999999999999999999/1000000000000000000000000000000', 1],
            ['999999999999999999999999999999/1000000000000000000000000000000', '999/1000', 1],
            ['-999999999999999999999999999999/1000000000000000000000000000000', '-999/1000', -1],
            ['-999999999999999999999999999999/1000000000000000000000000000000', -1, 1],
            ['-999999999999999999999999999999/1000000000000000000000000000000', '-10e-1', 1],
            ['-999999999999999999999999999999/1000000000000000000000000000000', '-0.999999999999999999999999999999', 0],
            ['-999999999999999999999999999999/1000000000000000000000000000000', '-0.999999999999999999999999999998', -1],
        ];
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testGetSign(string $number, int $sign) : void
    {
        self::assertSame($sign, BigRational::of($number)->getSign());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsZero(string $number, int $sign) : void
    {
        self::assertSame($sign === 0, BigRational::of($number)->isZero());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegative(string $number, int $sign) : void
    {
        self::assertSame($sign < 0, BigRational::of($number)->isNegative());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegativeOrZero(string $number, int $sign) : void
    {
        self::assertSame($sign <= 0, BigRational::of($number)->isNegativeOrZero());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositive(string $number, int $sign) : void
    {
        self::assertSame($sign > 0, BigRational::of($number)->isPositive());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositiveOrZero(string $number, int $sign) : void
    {
        self::assertSame($sign >= 0, BigRational::of($number)->isPositiveOrZero());
    }

    public static function providerSign() : array
    {
        return [
            ['0', 0],
            ['-0', 0],
            ['-2', -1],
            ['2', 1],
            ['0/123456', 0],
            ['-0/123456', 0],
            ['-1/23784738479837498273817307948739875387498374983749837984739874983749834384938493284934', -1],
            ['1/3478378924784729749873298479832792487498789012890843098490820480938092849032809480932840', 1],
        ];
    }

    /**
     * @param string      $number   The rational number to convert.
     * @param string|null $expected The expected decimal number, or null if an exception is expected.
     */
    #[DataProvider('providerToBigDecimal')]
    public function testToBigDecimal(string $number, ?string $expected) : void
    {
        if ($expected === null) {
            $this->expectException(RoundingNecessaryException::class);
        }

        $actual = BigRational::of($number)->toBigDecimal();

        if ($expected !== null) {
            self::assertBigDecimalEquals($expected, $actual);
        }
    }

    public static function providerToBigDecimal() : \Generator
    {
        $tests = [
            ['1', '1'],
            ['1/2', '0.5'],
            ['2/2', '1'],
            ['3/2', '1.5'],
            ['1/3', null],
            ['2/3', null],
            ['3/3', '1'],
            ['4/3', null],
            ['1/4', '0.25'],
            ['2/4', '0.5'],
            ['3/4', '0.75'],
            ['4/4', '1'],
            ['5/4', '1.25'],
            ['1/5', '0.2'],
            ['2/5', '0.4'],
            ['1/6', null],
            ['2/6', null],
            ['3/6', '0.5'],
            ['4/6', null],
            ['5/6', null],
            ['6/6', '1'],
            ['7/6', null],
            ['1/7', null],
            ['2/7', null],
            ['6/7', null],
            ['7/7', '1'],
            ['14/7', '2'],
            ['15/7', null],
            ['1/8', '0.125'],
            ['2/8', '0.25'],
            ['3/8', '0.375'],
            ['4/8', '0.5'],
            ['5/8', '0.625'],
            ['6/8', '0.75'],
            ['7/8', '0.875'],
            ['8/8', '1'],
            ['17/8', '2.125'],
            ['1/9', null],
            ['2/9', null],
            ['9/9', '1'],
            ['10/9', null],
            ['17/9', null],
            ['18/9', '2'],
            ['19/9', null],
            ['1/10', '0.1'],
            ['10/2', '5'],
            ['10/20', '0.5'],
            ['100/20', '5'],
            ['100/2', '50'],
            ['8/360', null],
            ['9/360', '0.025'],
            ['10/360', null],
            ['17/360', null],
            ['18/360', '0.05'],
            ['19/360', null],
            ['1/500', '0.002'],
            ['1/600', null],
            ['1/400', '0.0025'],
            ['1/800', '0.00125'],
            ['1/1600', '0.000625'],
            ['2/1600', '0.00125'],
            ['3/1600', '0.001875'],
            ['4/1600', '0.0025'],
            ['5/1600', '0.003125'],
            ['669433117850846623944075755499/3723692145740642445161938667297363281250', '0.0000000001797767086134066979625344023536861184'],
            ['669433117850846623944075755498/3723692145740642445161938667297363281250', null],
            ['669433117850846623944075755499/3723692145740642445161938667297363281251', null],

            ['438002367448868006942618029488152554057431119072727/9', '48666929716540889660290892165350283784159013230303'],
            ['438002367448868006942618029488152554057431119072728/9', null],

            ['1278347892548908779/181664161764972047166111224214546382427215576171875', '0.0000000000000000000000000000000070368744177664'],
            ['1278347892548908779/363328323529944094332222448429092764854431152343750', '0.0000000000000000000000000000000035184372088832'],
            ['1278347892548908778/363328323529944094332222448429092764854431152343750', null],
            ['1278347892548908779/363328323529944094332222448429092764854431152343751', null],

            ['1274512848871262052662/181119169279677131024612890541902743279933929443359375', null],
            ['1274512848871262052663/181119169279677131024612890541902743279933929443359375', '0.0000000000000000000000000000000070368744177664'],
            ['1274512848871262052664/181119169279677131024612890541902743279933929443359375', null],
        ];

        foreach ($tests as [$number, $expected]) {
            yield [$number, $expected];
            yield ['-' . $number, $expected === null ? null : '-' . $expected];
        }
    }

    #[DataProvider('providerToScale')]
    public function testToScale(string $number, int $scale, RoundingMode $roundingMode, string $expected) : void
    {
        $number = BigRational::of($number);

        if (self::isException($expected)) {
            $this->expectException($expected);
        }

        $actual = $number->toScale($scale, $roundingMode);

        if (! self::isException($expected)) {
            self::assertBigDecimalEquals($expected, $actual);
        }
    }

    public static function providerToScale() : array
    {
        return [
            ['1/8', 3, RoundingMode::UNNECESSARY, '0.125'],
            ['1/16', 3, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            ['1/16', 3, RoundingMode::HALF_DOWN, '0.062'],
            ['1/16', 3, RoundingMode::HALF_UP, '0.063'],
            ['1/9', 30, RoundingMode::DOWN, '0.111111111111111111111111111111'],
            ['1/9', 30, RoundingMode::UP, '0.111111111111111111111111111112'],
            ['1/9', 100, RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
        ];
    }

    /**
     * @param int|string $rational The rational number to test.
     * @param int        $integer  The expected integer value.
     */
    #[DataProvider('providerToInt')]
    public function testToInt(int|string $rational, int $integer) : void
    {
        self::assertSame($integer, BigRational::of($rational)->toInt());
    }

    public static function providerToInt() : array
    {
        return [
            [PHP_INT_MAX, PHP_INT_MAX],
            [PHP_INT_MIN, PHP_INT_MIN],
            [PHP_INT_MAX . '0/10', PHP_INT_MAX],
            [PHP_INT_MIN . '0/10', PHP_INT_MIN],
            ['246913578/2', 123456789],
            ['-246913578/2', -123456789],
            ['625/25', 25],
            ['-625/25', -25],
            ['0/3', 0],
            ['-0/3', 0],
        ];
    }

    /**
     * @param string $number A valid rational number that cannot safely be converted to a native integer.
     */
    #[DataProvider('providerToIntThrowsException')]
    public function testToIntThrowsException(string $number) : void
    {
        $this->expectException(MathException::class);
        BigRational::of($number)->toInt();
    }

    public static function providerToIntThrowsException() : array
    {
        return [
            ['-999999999999999999999999999999'],
            ['9999999999999999999999999999999/2'],
            ['1/2'],
            ['2/3'],
        ];
    }

    public function testIdentityOperationResultsInDifferentToFloatValueWithoutSimplification() : void
    {
        $expectedValue = 11.46;
        $conversionFactor = BigRational::of('0.45359237');
        $value = BigRational::of($expectedValue);

        $identicalValueAfterMathOperations = $value->multipliedBy($conversionFactor)
            ->dividedBy($conversionFactor)
            ->multipliedBy($conversionFactor)
            ->dividedBy($conversionFactor)
            ->multipliedBy($conversionFactor)
            ->dividedBy($conversionFactor);

        self::assertSame($expectedValue, $identicalValueAfterMathOperations->toFloat());

        // Assert that simplification is required and the test would fail without it
        self::assertNotSame(
            $expectedValue,
            $identicalValueAfterMathOperations->getNumerator()->toFloat() / $identicalValueAfterMathOperations->getDenominator()->toFloat(),
        );
    }

    public function testToFloatConversionPerformsSimplificationToPreventOverflow() : void
    {
        $int = BigInteger::of('1e4000');
        $val = BigRational::nd($int, $int);

        self::assertInfinite($val->getNumerator()->toFloat());
        // Assert that simplification is required and the test would fail without it
        self::assertSame(1.0, $val->toFloat());
    }

    /**
     * @param string $value The big decimal value.
     * @param float  $float The expected float value.
     */
    #[DataProvider('providerToFloat')]
    public function testToFloat(string $value, float $float) : void
    {
        self::assertSame($float, BigRational::of($value)->toFloat());
    }

    public static function providerToFloat() : array
    {
        return [
            ['0', 0.0],
            ['1.6', 1.6],
            ['-1.6', -1.6],
            ['1000000000000000000000000000000000000000/3', 3.3333333333333333333333333333333333e+38],
            ['-2/300000000000000000000000000000000000000', -6.666666666666666666666666666666666e-39],
            ['9.9e3000', INF],
            ['-9.9e3000', -INF],
        ];
    }

    /**
     * @param string $numerator   The numerator.
     * @param string $denominator The denominator.
     * @param string $expected    The expected string output.
     */
    #[DataProvider('providerToString')]
    public function testToString(string $numerator, string $denominator, string $expected) : void
    {
        self::assertBigRationalEquals($expected, BigRational::nd($numerator, $denominator));
    }

    public static function providerToString() : array
    {
        return [
            ['-1', '1', '-1'],
            ['2', '1', '2'],
            ['1', '2', '1/2'],
            ['-1', '-2', '1/2'],
            ['1', '-2', '-1/2'],
            ['34327948737247817984738927598572389', '32565046546', '34327948737247817984738927598572389/32565046546'],
            ['34327948737247817984738927598572389', '-32565046546', '-34327948737247817984738927598572389/32565046546'],
            ['34327948737247817984738927598572389', '1', '34327948737247817984738927598572389'],
            ['34327948737247817984738927598572389', '-1', '-34327948737247817984738927598572389'],
        ];
    }

    public function testSerialize() : void
    {
        $numerator   = '-1234567890987654321012345678909876543210123456789';
        $denominator = '347827348278374374263874681238374983729873401984091287439827467286';

        $rational = BigRational::nd($numerator, $denominator);

        self::assertBigRationalInternalValues($numerator, $denominator, \unserialize(\serialize($rational)));
    }

    public function testDirectCallToUnserialize() : void
    {
        $this->expectException(\LogicException::class);
        BigRational::nd(1, 2)->__unserialize([]);
    }
}
