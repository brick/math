<?php

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Unit tests for class BigRational.
 */
class BigRationalTest extends AbstractTestCase
{
    /**
     * @dataProvider providerNd
     *
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     * @param string $n           The input numerator.
     * @param string $d           The input denominator.
     */
    public function testNd($numerator, $denominator, $n, $d)
    {
        $rational = BigRational::nd($n, $d);
        $this->assertBigRationalInternalValues($numerator, $denominator, $rational);
    }

    /**
     * @return array
     */
    public function providerNd()
    {
        return [
            ['7', '1', '7', 1],
            ['7', '36', 7, 36],
            ['-7', '36', 7, -36],
            ['9', '15', '-9', -15],
            ['-98765432109876543210', '12345678901234567890', '-98765432109876543210', '12345678901234567890'],
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testNdWithZeroDenominator()
    {
        BigRational::nd(1, 0);
    }

    /**
     * @dataProvider providerOf
     *
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     * @param string $string      The string to parse.
     */
    public function testOf($numerator, $denominator, $string)
    {
        $rational = BigRational::of($string);
        $this->assertBigRationalInternalValues($numerator, $denominator, $rational);
    }

    /**
     * @return array
     */
    public function providerOf()
    {
        return [
            ['123', '456', '123/456'],
            ['123', '456', '+123/456'],
            ['-2345', '6789', '-2345/6789'],
            ['123456', '1', '123456'],
            ['-1234567', '1', '-1234567'],
            ['-1234567890987654321012345678909876543210', '9999', '-1234567890987654321012345678909876543210/9999'],
            ['1230000', '1', '123e4'],
            ['1125', '1000', '1.125'],
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testOfWithZeroDenominator()
    {
        BigRational::of('2/0');
    }

    /**
     * @dataProvider providerOfInvalidString
     * @expectedException \Brick\Math\Exception\NumberFormatException
     *
     * @param string $string An invalid string representation.
     */
    public function testOfInvalidString($string)
    {
        BigRational::of($string);
    }

    /**
     * @return array
     */
    public function providerOfInvalidString()
    {
        return [
            ['123/-456'],
            ['1e4/2'],
            [' 1/2'],
            ['1/2 '],
            ['+'],
            ['-'],
            ['/',]
        ];
    }

    public function testZero()
    {
        $this->assertBigRationalInternalValues('0', '1', BigRational::zero());
        $this->assertSame(BigRational::zero(), BigRational::zero());
    }

    public function testOne()
    {
        $this->assertBigRationalInternalValues('1', '1', BigRational::one());
        $this->assertSame(BigRational::one(), BigRational::one());
    }

    public function testTen()
    {
        $this->assertBigRationalInternalValues('10', '1', BigRational::ten());
        $this->assertSame(BigRational::ten(), BigRational::ten());
    }

    public function testAccessors()
    {
        $rational = BigRational::nd(123456789, 987654321);

        $this->assertBigIntegerEquals('123456789', $rational->numerator());
        $this->assertBigIntegerEquals('987654321', $rational->denominator());
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $values The values to test.
     * @param string $min    The expected minimum value, in rational form.
     */
    public function testMin(array $values, $min)
    {
        $this->assertBigRationalEquals($min, BigRational::min(... $values));
    }

    /**
     * @return array
     */
    public function providerMin()
    {
        return [
            [['1/2', '1/4', '1/3'], '1/4'],
            [['1/2', '0.1', '1/3'], '1/10'],
            [['-0.25', '-0.3', '-1/8', '123456789123456789123456789', 2e25], '-3/10'],
            [['1e30', '123456789123456789123456789/3', 2e26], '123456789123456789123456789/3'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMinOfZeroValuesThrowsException()
    {
        BigRational::min();
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $values The values to test.
     * @param string $max    The expected maximum value, in rational form.
     */
    public function testMax(array $values, $max)
    {
        $this->assertBigRationalEquals($max, BigRational::max(... $values));
    }

    /**
     * @return array
     */
    public function providerMax()
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxOfZeroValuesThrowsException()
    {
        BigRational::max();
    }

    /**
     * @dataProvider providerQuotientAndRemainder
     *
     * @param string $rational  The rational number to test.
     * @param string $quotient  The expected quotient.
     * @param string $remainder The expected remainder.
     */
    public function testQuotientAndRemainder($rational, $quotient, $remainder)
    {
        $rational = BigRational::of($rational);

        $this->assertBigIntegerEquals($quotient, $rational->quotient());
        $this->assertBigIntegerEquals($remainder, $rational->remainder());

        $quotientAndRemainder = $rational->quotientAndRemainder();

        $this->assertBigIntegerEquals($quotient, $quotientAndRemainder[0]);
        $this->assertBigIntegerEquals($remainder, $quotientAndRemainder[1]);
    }

    /**
     * @return array
     */
    public function providerQuotientAndRemainder()
    {
        return [
            ['1000/3', '333', '1'],
            ['895/400', '2', '95'],
            ['-2.5', '-2', '-5'],
            [-2, '-2', '0'],
        ];
    }

    /**
     * @dataProvider providerPlus
     *
     * @param string $rational The rational number to test.
     * @param string $plus     The number to add.
     * @param string $expected The expected rational number result.
     */
    public function testPlus($rational, $plus, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->plus($plus));
    }

    /**
     * @return array
     */
    public function providerPlus()
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
     * @dataProvider providerMinus
     *
     * @param string $rational The rational number to test.
     * @param string $minus    The number to subtract.
     * @param string $expected The expected rational number result.
     */
    public function testMinus($rational, $minus, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->minus($minus));
    }

    /**
     * @return array
     */
    public function providerMinus()
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
     * @dataProvider providerMultipliedBy
     *
     * @param string $rational The rational number to test.
     * @param string $minus    The number to multiply.
     * @param string $expected The expected rational number result.
     */
    public function testMultipliedBy($rational, $minus, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->multipliedBy($minus));
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
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
     * @dataProvider providerDividedBy
     *
     * @param string $rational The rational number to test.
     * @param string $minus    The number to multiply.
     * @param string $expected The expected rational number result.
     */
    public function testDividedBy($rational, $minus, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->dividedBy($minus));
    }

    /**
     * @return array
     */
    public function providerDividedBy()
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
     * @dataProvider providerPower
     *
     * @param string $number   The base number.
     * @param int    $exponent The exponent to apply.
     * @param string $expected The expected result.
     */
    public function testPower($number, $exponent, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($number)->power($exponent));
    }

    /**
     * @return array
     */
    public function providerPower()
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
     * @dataProvider providerReciprocal
     *
     * @param string $rational The rational number to test.
     * @param string $expected The expected reciprocal.
     */
    public function testReciprocal($rational, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->reciprocal());
    }

    /**
     * @return array
     */
    public function providerReciprocal()
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

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testReciprocalOfZeroThrowsException()
    {
        BigRational::nd(0, 2)->reciprocal();
    }

    /**
     * @dataProvider providerAbs
     *
     * @param string $rational The rational number to test.
     * @param string $expected The expected absolute number.
     */
    public function testAbs($rational, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->abs());
    }

    /**
     * @return array
     */
    public function providerAbs()
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['-1', '1'],
            ['123/456', '123/456'],
            ['-234/567', '234/567'],
            ['-489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445'],
        ];
    }

    /**
     * @dataProvider providerNegated
     *
     * @param string $rational The rational number to test.
     * @param string $expected The expected negated number.
     */
    public function testNegated($rational, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->negated());
    }

    /**
     * @return array
     */
    public function providerNegated()
    {
        return [
            ['0', '0'],
            ['1', '-1'],
            ['-1', '1'],
            ['123/456', '-123/456'],
            ['-234/567', '234/567'],
            ['-489798742123504998877665/387590928349859112233445', '489798742123504998877665/387590928349859112233445'],
            ['489798742123504998877665/387590928349859112233445', '-489798742123504998877665/387590928349859112233445'],
        ];
    }

    /**
     * @dataProvider providerSimplified
     *
     * @param string $rational The rational number to test.
     * @param string $expected The expected negated number.
     */
    public function testSimplified($rational, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::of($rational)->simplified());
    }

    /**
     * @return array
     */
    public function providerSimplified()
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
     * @dataProvider providerCompareTo
     *
     * @param string $a   The first number to compare.
     * @param string $b   The second number to compare.
     * @param int    $cmp The comparison value.
     */
    public function testCompareTo($a, $b, $cmp)
    {
        $this->assertSame($cmp, BigRational::of($a)->compareTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a   The first number to compare.
     * @param string $b   The second number to compare.
     * @param int    $cmp The comparison value.
     */
    public function testIsEqualTo($a, $b, $cmp)
    {
        $this->assertSame($cmp == 0, BigRational::of($a)->isEqualTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a   The first number to compare.
     * @param string $b   The second number to compare.
     * @param int    $cmp The comparison value.
     */
    public function testIsLessThan($a, $b, $cmp)
    {
        $this->assertSame($cmp < 0, BigRational::of($a)->isLessThan($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a   The first number to compare.
     * @param string $b   The second number to compare.
     * @param int    $cmp The comparison value.
     */
    public function testIsLessThanOrEqualTo($a, $b, $cmp)
    {
        $this->assertSame($cmp <= 0, BigRational::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a   The first number to compare.
     * @param string $b   The second number to compare.
     * @param int    $cmp The comparison value.
     */
    public function testIsGreaterThan($a, $b, $cmp)
    {
        $this->assertSame($cmp > 0, BigRational::of($a)->isGreaterThan($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a   The first number to compare.
     * @param string $b   The second number to compare.
     * @param int    $cmp The comparison value.
     */
    public function testIsGreaterThanOrEqualTo($a, $b, $cmp)
    {
        $this->assertSame($cmp >= 0, BigRational::of($a)->isGreaterThanOrEqualTo($b));
    }

    /**
     * @return array
     */
    public function providerCompareTo()
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
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testSign($number, $sign)
    {
        $this->assertSame($sign, BigRational::of($number)->sign());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsZero($number, $sign)
    {
        $this->assertSame($sign == 0, BigRational::of($number)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsNegative($number, $sign)
    {
        $this->assertSame($sign < 0, BigRational::of($number)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsNegativeOrZero($number, $sign)
    {
        $this->assertSame($sign <= 0, BigRational::of($number)->isNegativeOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsPositive($number, $sign)
    {
        $this->assertSame($sign > 0, BigRational::of($number)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsPositiveOrZero($number, $sign)
    {
        $this->assertSame($sign >= 0, BigRational::of($number)->isPositiveOrZero());
    }

    /**
     * @return array
     */
    public function providerSign()
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
     * @dataProvider providerToBigDecimal
     *
     * @param string      $number   The rational number to convert.
     * @param string|null $expected The expected decimal number, or null if an exception is expected.
     */
    public function testToBigDecimal($number, $expected)
    {
        if ($expected === null) {
            $this->setExpectedException(RoundingNecessaryException::class);
        }

        $actual = BigRational::of($number)->toBigDecimal();

        if ($expected !== null) {
            $this->assertBigDecimalEquals($expected, $actual);
        }
    }

    /**
     * @return \Generator
     */
    public function providerToBigDecimal()
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

        foreach ($tests as list ($number, $expected)) {
            yield [$number, $expected];
            yield ['-' . $number, $expected === null ? null : '-' . $expected];
        }
    }

    /**
     * @dataProvider providerToScale
     *
     * @param string $number
     * @param int    $scale
     * @param int    $roundingMode
     * @param string $expected
     */
    public function testToScale($number, $scale, $roundingMode, $expected)
    {
        $number = BigRational::of($number);

        if ($this->isException($expected)) {
            $this->setExpectedException($expected);
        }

        $actual = $number->toScale($scale, $roundingMode);

        if (! $this->isException($expected)) {
            $this->assertBigDecimalEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerToScale()
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
     * @dataProvider providerToInteger
     *
     * @param string $rational The rational number to test.
     * @param int    $integer  The expected integer value.
     */
    public function testToInteger($rational, $integer)
    {
        $this->assertSame($integer, BigRational::of($rational)->toInteger());
    }

    /**
     * @return array
     */
    public function providerToInteger()
    {
        return [
            [PHP_INT_MAX, PHP_INT_MAX],
            [~PHP_INT_MAX, ~PHP_INT_MAX],
            [PHP_INT_MAX . '0/10', PHP_INT_MAX],
            [~PHP_INT_MAX . '0/10', ~PHP_INT_MAX],
            ['246913578/2', 123456789],
            ['-246913578/2', -123456789],
            ['625/25', 25],
            ['-625/25', -25],
            ['0/3', 0],
            ['-0/3', 0],
        ];
    }

    /**
     * @dataProvider providerToIntegerThrowsException
     * @expectedException \Brick\Math\Exception\ArithmeticException
     *
     * @param string $number A valid rational number that cannot safely be converted to a native integer.
     */
    public function testToIntegerThrowsException($number)
    {
        BigRational::of($number)->toInteger();
    }

    /**
     * @return array
     */
    public function providerToIntegerThrowsException()
    {
        return [
            ['-999999999999999999999999999999'],
            ['9999999999999999999999999999999/2'],
            ['1/2'],
            ['2/3'],
        ];
    }

    /**
     * @dataProvider providerToFloat
     *
     * @param string $value The big decimal value.
     * @param float  $float The expected float value.
     */
    public function testToFloat($value, $float)
    {
        $this->assertSame($float, BigRational::of($value)->toFloat());
    }

    /**
     * @return array
     */
    public function providerToFloat()
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
     * @dataProvider providerToString
     *
     * @param string $numerator   The numerator.
     * @param string $denominator The denominator.
     * @param string $expected    The expected string output.
     */
    public function testToString($numerator, $denominator, $expected)
    {
        $this->assertBigRationalEquals($expected, BigRational::nd($numerator, $denominator));
    }

    /**
     * @return array
     */
    public function providerToString()
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

    public function testSerialize()
    {
        $numerator   = '-1234567890987654321012345678909876543210123456789';
        $denominator = '347827348278374374263874681238374983729873401984091287439827467286';

        $rational = BigRational::nd($numerator, $denominator);

        $this->assertBigRationalInternalValues($numerator, $denominator, unserialize(serialize($rational)));
    }

    /**
     * @expectedException \LogicException
     */
    public function testDirectCallToUnserialize()
    {
        BigRational::nd(1, 2)->unserialize('123/456');
    }
}
