<?php

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;

/**
 * Unit tests for class BigRational.
 */
class BigRationalTest extends AbstractTestCase
{
    /**
     * @dataProvider providerOf
     *
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     * @param mixed  ...$args     The arguments to the factory method.
     */
    public function testOf($numerator, $denominator, ...$args)
    {
        $rational = BigRational::of(... $args);
        $this->assertBigRationalEquals($numerator, $denominator, $rational);
    }

    /**
     * @return array
     */
    public function providerOf()
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
     * @dataProvider providerParse
     *
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     * @param string $string      The string to parse.
     */
    public function testParse($numerator, $denominator, $string)
    {
        $rational = BigRational::parse($string);
        $this->assertBigRationalEquals($numerator, $denominator, $rational);
    }

    /**
     * @return array
     */
    public function providerParse()
    {
        return [
            ['123', '456', '123/456'],
            ['-2345', '6789', '-2345/6789'],
            ['123456', '1', '123456'],
            ['-1234567', '1', '-1234567'],
            ['-1234567890987654321012345678909876543210', '9999', '-1234567890987654321012345678909876543210/9999'],
        ];
    }

    /**
     * @dataProvider providerParseInvalidString
     * @expectedException \InvalidArgumentException
     *
     * @param string $string An invalid string representation.
     */
    public function testParseInvalidString($string)
    {
        BigRational::parse($string);
    }

    /**
     * @return array
     */
    public function providerParseInvalidString()
    {
        return [
            ['123/-456'],
            ['+123/456'],
            ['123e4'],
            ['1e4/2'],
            ['1.2'],
            [' 1/2'],
            ['1/2 '],
        ];
    }

    public function testAccessors()
    {
        $rational = BigRational::of(123456789, 987654321);

        $this->assertBigIntegerEquals('123456789', $rational->getNumerator());
        $this->assertBigIntegerEquals('987654321', $rational->getDenominator());
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
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->plus($plus));
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            ['123/456', 1, '579/456'],
            ['123/456', BigInteger::of(2), '1035/456'],
            ['123/456', BigRational::of(2, 3), '1281/1368'],
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
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->minus($minus));
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
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->multipliedBy($minus));
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
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->dividedBy($minus));
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
     * @dataProvider providerReciprocal
     *
     * @param string $rational The rational number to test.
     * @param string $expected The expected reciprocal.
     */
    public function testReciprocal($rational, $expected)
    {
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->reciprocal());
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
     * @expectedException \Brick\Math\ArithmeticException
     */
    public function testReciprocalOfZeroThrowsException()
    {
        BigRational::of(0, 2)->reciprocal();
    }

    /**
     * @dataProvider providerAbs
     *
     * @param string $rational The rational number to test.
     * @param string $expected The expected absolute number.
     */
    public function testAbs($rational, $expected)
    {
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->abs());
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
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->negated());
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
        $rational = BigRational::parse($rational);
        $this->assertSame($expected, (string) $rational->simplified());
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
        $this->assertSame($cmp, BigRational::parse($a)->compareTo($b));
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
        $this->assertSame($cmp == 0, BigRational::parse($a)->isEqualTo($b));
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
        $this->assertSame($cmp < 0, BigRational::parse($a)->isLessThan($b));
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
        $this->assertSame($cmp <= 0, BigRational::parse($a)->isLessThanOrEqualTo($b));
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
        $this->assertSame($cmp > 0, BigRational::parse($a)->isGreaterThan($b));
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
        $this->assertSame($cmp >= 0, BigRational::parse($a)->isGreaterThanOrEqualTo($b));
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
        ];
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testGetSign($number, $sign)
    {
        $this->assertSame($sign, BigRational::parse($number)->getSign());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsZero($number, $sign)
    {
        $this->assertSame($sign == 0, BigRational::parse($number)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsNegative($number, $sign)
    {
        $this->assertSame($sign < 0, BigRational::parse($number)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsNegativeOrZero($number, $sign)
    {
        $this->assertSame($sign <= 0, BigRational::parse($number)->isNegativeOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsPositive($number, $sign)
    {
        $this->assertSame($sign > 0, BigRational::parse($number)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    public function testIsPositiveOrZero($number, $sign)
    {
        $this->assertSame($sign >= 0, BigRational::parse($number)->isPositiveOrZero());
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
     * @dataProvider providerToString
     *
     * @param string $numerator   The numerator.
     * @param string $denominator The denominator.
     * @param string $toString    The expected string output.
     */
    public function testToString($numerator, $denominator, $toString)
    {
        $this->assertSame($toString, (string) BigRational::of($numerator, $denominator));
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
}
