<?php

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Unit tests for class BigDecimal.
 */
class BigDecimalTest extends AbstractTestCase
{
    /**
     * @dataProvider providerOf
     *
     * @param string|number $value         The value to convert to a BigDecimal.
     * @param string        $unscaledValue The expected unscaled value.
     * @param int           $scale         The expected scale.
     */
    public function testOf($value, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($value));
    }

    /**
     * @return array
     */
    public function providerOf()
    {
        return [
            [0, '0', 0],
            [1, '1', 0],
            [-1, '-1', 0],
            [123456789, '123456789', 0],
            [-123456789, '-123456789', 0],
            [PHP_INT_MAX, (string) PHP_INT_MAX, 0],
            [~PHP_INT_MAX, (string) ~PHP_INT_MAX, 0],

            [0.0, '0', 0],
            [0.1, '1', 1],
            [1.0, '1', 0],
            [1.1, '11', 1],

            ['0', '0', 0],
            ['+0', '0', 0],
            ['-0', '0', 0],
            ['00', '0', 0],
            ['+00', '0', 0],
            ['-00', '0', 0],

            ['1', '1', 0],
            ['+1', '1', 0],
            ['-1', '-1', 0],
            ['01', '1', 0],
            ['+01', '1', 0],
            ['-01', '-1', 0],

            ['0.0', '0', 1],
            ['+0.0', '0', 1],
            ['-0.0', '0', 1],
            ['00.0', '0', 1],
            ['+00.0', '0', 1],
            ['-00.0', '0', 1],

            ['1.0', '10', 1],
            ['+1.0', '10', 1],
            ['-1.0', '-10', 1],
            ['01.0', '10', 1],
            ['+01.0', '10', 1],
            ['-01.0', '-10', 1],

            ['0.1', '1', 1],
            ['+0.1', '1', 1],
            ['-0.1', '-1', 1],
            ['0.10', '10', 2],
            ['+0.10', '10', 2],
            ['-0.10', '-10', 2],
            ['0.010', '10', 3],
            ['+0.010', '10', 3],
            ['-0.010', '-10', 3],

            ['00.1', '1', 1],
            ['+00.1', '1', 1],
            ['-00.1', '-1', 1],
            ['00.10', '10', 2],
            ['+00.10', '10', 2],
            ['-00.10', '-10', 2],
            ['00.010', '10', 3],
            ['+00.010', '10', 3],
            ['-00.010', '-10', 3],

            ['01.1', '11', 1],
            ['+01.1', '11', 1],
            ['-01.1', '-11', 1],
            ['01.010', '1010', 3],
            ['+01.010', '1010', 3],
            ['-01.010', '-1010', 3],

            ['0e-2', '0', 2],
            ['0e-1', '0', 1],
            ['0e-0', '0', 0],
            ['0e0', '0', 0],
            ['0e1', '0', 0],
            ['0e2', '0', 0],
            ['0e+0', '0', 0],
            ['0e+1','0', 0],
            ['0e+2','0', 0],

            ['0.0e-2', '0', 3],
            ['0.0e-1', '0', 2],
            ['0.0e-0', '0', 1],
            ['0.0e0', '0', 1],
            ['0.0e1', '0', 0],
            ['0.0e2', '0', 0],
            ['0.0e+0', '0', 1],
            ['0.0e+1','0', 0],
            ['0.0e+2','0', 0],

            ['0.1e-2', '1', 3],
            ['0.1e-1', '1', 2],
            ['0.1e-0', '1', 1],
            ['0.1e0', '1', 1],
            ['0.1e1', '1', 0],
            ['0.1e2', '10', 0],
            ['0.1e+0', '1', 1],
            ['0.1e+1','1', 0],
            ['0.1e+2','10', 0],
            ['1.23e+011', '123000000000', 0],
            ['1.23e-011', '123', 13],

            ['0.01e-2', '1', 4],
            ['0.01e-1', '1', 3],
            ['0.01e-0', '1', 2],
            ['0.01e0', '1', 2],
            ['0.01e1', '1', 1],
            ['0.01e2', '1', 0],
            ['0.01e+0', '1', 2],
            ['0.01e+1','1', 1],
            ['0.01e+2','1', 0],

            ['0.10e-2', '10', 4],
            ['0.10e-1', '10', 3],
            ['0.10e-0', '10', 2],
            ['0.10e0', '10', 2],
            ['0.10e1', '10', 1],
            ['0.10e2', '10', 0],
            ['0.10e+0', '10', 2],
            ['0.10e+1','10', 1],
            ['0.10e+2','10', 0],

            ['00.10e-2', '10', 4],
            ['+00.10e-1', '10', 3],
            ['-00.10e-0', '-10', 2],
            ['00.10e0', '10', 2],
            ['+00.10e1', '10', 1],
            ['-00.10e2', '-10', 0],
            ['00.10e+0', '10', 2],
            ['+00.10e+1','10', 1],
            ['-00.10e+2','-10', 0],
        ];
    }

    /**
     * @dataProvider providerOfInvalidValueThrowsException
     * @expectedException \Brick\Math\Exception\NumberFormatException
     *
     * @param string $value
     */
    public function testOfInvalidValueThrowsException($value)
    {
        BigDecimal::of($value);
    }

    /**
     * @return array
     */
    public function providerOfInvalidValueThrowsException()
    {
        return [
            [''],
            ['a'],
            [' 1'],
            ['1 '],
            ['1.'],
            ['.1'],
            ['+'],
            ['-'],
            ['+a'],
            ['-a'],
            [INF],
            [-INF],
            [NAN],
        ];
    }

    public function testOfBigDecimalReturnsThis()
    {
        $decimal = BigDecimal::of(123);

        $this->assertSame($decimal, BigDecimal::of($decimal));
    }

    /**
     * @dataProvider providerOfUnscaledValue
     *
     * @param string|int $unscaledValue         The unscaled value of the BigDecimal to create.
     * @param int        $scale                 The scale of the BigDecimal to create.
     * @param string     $expectedUnscaledValue The expected result unscaled value.
     */
    public function testOfUnscaledValue($unscaledValue, $scale, $expectedUnscaledValue)
    {
        $number = BigDecimal::ofUnscaledValue($unscaledValue, $scale);
        $this->assertBigDecimalInternalValues($expectedUnscaledValue, $scale, $number);
    }

    /**
     * @return array
     */
    public function providerOfUnscaledValue()
    {
        return [
            [123456789, 0, '123456789'],
            [123456789, 1, '123456789'],
            [-123456789, 0, '-123456789'],
            [-123456789, 1, '-123456789'],

            ['123456789012345678901234567890', 0, '123456789012345678901234567890'],
            ['123456789012345678901234567890', 1, '123456789012345678901234567890'],
            ['+123456789012345678901234567890', 0, '123456789012345678901234567890'],
            ['+123456789012345678901234567890', 1, '123456789012345678901234567890'],
            ['-123456789012345678901234567890', 0, '-123456789012345678901234567890'],
            ['-123456789012345678901234567890', 1, '-123456789012345678901234567890'],

            ['0123456789012345678901234567890', 0, '123456789012345678901234567890'],
            ['0123456789012345678901234567890', 1, '123456789012345678901234567890'],
            ['+0123456789012345678901234567890', 0, '123456789012345678901234567890'],
            ['+0123456789012345678901234567890', 1, '123456789012345678901234567890'],
            ['-0123456789012345678901234567890', 0, '-123456789012345678901234567890'],
            ['-0123456789012345678901234567890', 1, '-123456789012345678901234567890'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOfUnscaledValueWithNegativeScaleThrowsException()
    {
        BigDecimal::ofUnscaledValue('0', -1);
    }

    public function testZero()
    {
        $this->assertBigDecimalInternalValues('0', 0, BigDecimal::zero());
        $this->assertSame(BigDecimal::zero(), BigDecimal::zero());
    }

    public function testOne()
    {
        $this->assertBigDecimalInternalValues('1', 0, BigDecimal::one());
        $this->assertSame(BigDecimal::one(), BigDecimal::one());
    }

    public function testTen()
    {
        $this->assertBigDecimalInternalValues('10', 0, BigDecimal::ten());
        $this->assertSame(BigDecimal::ten(), BigDecimal::ten());
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $values The values to test.
     * @param string $min    The expected minimum value.
     */
    public function testMin(array $values, $min)
    {
        $this->assertBigDecimalEquals($min, BigDecimal::min(... $values));
    }

    /**
     * @return array
     */
    public function providerMin()
    {
        return [
            [[0, 1, -1], '-1'],
            [[0, 1, -1, -1.2], '-1.2'],
            [['1e30', '123456789123456789123456789', 2e25], '20000000000000000000000000'],
            [['1e30', '123456789123456789123456789', 2e26], '123456789123456789123456789'],
            [[0, '10', '5989', '-3/3'], '-1'],
            [['-0.0000000000000000000000000000001', '0'], '-0.0000000000000000000000000000001'],
            [['0.00000000000000000000000000000001', '0'], '0'],
            [['-1', '1', '2', '3', '-2973/30'], '-99.1'],
            [['999999999999999999999999999.99999999999', '1000000000000000000000000000'], '999999999999999999999999999.99999999999'],
            [['-999999999999999999999999999.99999999999', '-1000000000000000000000000000'], '-1000000000000000000000000000'],
            [['9.9e50', '1e50'], '100000000000000000000000000000000000000000000000000'],
            [['9.9e50', '1e51'], '990000000000000000000000000000000000000000000000000'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMinOfZeroValuesThrowsException()
    {
        BigDecimal::min();
    }

    /**
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     */
    public function testMinOfNonDecimalValuesThrowsException()
    {
        BigDecimal::min(1, '1/3');
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $values The values to test.
     * @param string $max    The expected maximum value.
     */
    public function testMax(array $values, $max)
    {
        $this->assertBigDecimalEquals($max, BigDecimal::max(... $values));
    }

    /**
     * @return array
     */
    public function providerMax()
    {
        return [
            [[0, 0.9, -1.00], '0.9'],
            [[0, 0.01, -1, -1.2], '0.01'],
            [[0, 0.01, -1, -1.2, '2e-1'], '0.2'],
            [['1e-30', '123456789123456789123456789', 2e25], '123456789123456789123456789'],
            [['1e-30', '123456789123456789123456789', 2e26], '200000000000000000000000000'],
            [[0, '10', '5989', '-1'], '5989'],
            [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1'], '5989.000000000000000000000000000000001'],
            [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1', '5990'], '5990'],
            [['-0.0000000000000000000000000000001', 0], '0'],
            [['0.00000000000000000000000000000001', '0'], '0.00000000000000000000000000000001'],
            [['-1', '1', '2', '3', '-99.1'], '3'],
            [['-1', '1', '2', '3', '-99.1', '31/10'], '3.1'],
            [['999999999999999999999999999.99999999999', '1000000000000000000000000000'], '1000000000000000000000000000'],
            [['-999999999999999999999999999.99999999999', '-1000000000000000000000000000'], '-999999999999999999999999999.99999999999'],
            [['9.9e50', '1e50'], '990000000000000000000000000000000000000000000000000'],
            [['9.9e50', '1e51'], '1000000000000000000000000000000000000000000000000000'],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxOfZeroValuesThrowsException()
    {
        BigDecimal::max();
    }

    /**
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     */
    public function testMaxOfNonDecimalValuesThrowsException()
    {
        BigDecimal::min(1, '3/7');
    }

    /**
     * @dataProvider providerPlus
     *
     * @param string $a             The base number.
     * @param string $b             The number to add.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    public function testPlus($a, $b, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->plus($b));
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            ['123',    '999',    '1122',   0],
            ['123',    '999.0',  '11220',  1],
            ['123',    '999.00', '112200', 2],
            ['123.0',  '999',    '11220',  1],
            ['123.0',  '999.0',  '11220',  1],
            ['123.0',  '999.00', '112200', 2],
            ['123.00', '999',    '112200', 2],
            ['123.00', '999.0',  '112200', 2],
            ['123.00', '999.00', '112200', 2],

            ['123',    '-999',    '-876',   0],
            ['123',    '-999.0',  '-8760',  1],
            ['123',    '-999.00', '-87600', 2],
            ['123.0',  '-999',    '-8760',  1],
            ['123.0',  '-999.0',  '-8760',  1],
            ['123.0',  '-999.00', '-87600', 2],
            ['123.00', '-999',    '-87600', 2],
            ['123.00', '-999.0',  '-87600', 2],
            ['123.00', '-999.00', '-87600', 2],

            ['-123',    '999',    '876',   0],
            ['-123',    '999.0',  '8760',  1],
            ['-123',    '999.00', '87600', 2],
            ['-123.0',  '999',    '8760',  1],
            ['-123.0',  '999.0',  '8760',  1],
            ['-123.0',  '999.00', '87600', 2],
            ['-123.00', '999',    '87600', 2],
            ['-123.00', '999.0',  '87600', 2],
            ['-123.00', '999.00', '87600', 2],

            ['-123',    '-999',    '-1122',   0],
            ['-123',    '-999.0',  '-11220',  1],
            ['-123',    '-999.00', '-112200', 2],
            ['-123.0',  '-999',    '-11220',  1],
            ['-123.0',  '-999.0',  '-11220',  1],
            ['-123.0',  '-999.00', '-112200', 2],
            ['-123.00', '-999',    '-112200', 2],
            ['-123.00', '-999.0',  '-112200', 2],
            ['-123.00', '-999.00', '-112200', 2],

            ['23487837847837428335.322387091', '309049304233535454687656.2392', '309072792071383292115991561587091', 9],
            ['-234878378478328335.322387091', '309049304233535154687656.232', '309049069355156676359320909612909', 9],
            ['234878378478328335.3227091', '-3090495154687656.231343344452', '231787883323640679091365755548', 12],
            ['-23487837847833435.3231', '-3090495154687656.231343344452', '-26578333002521091554443344452', 12],

            ['1234568798347983.2334899238921', '0', '12345687983479832334899238921', 13],
            ['-0.00223287647368738736428467863784', '0.000', '-223287647368738736428467863784', 32],
        ];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param string $a             The base number.
     * @param string $b             The number to subtract.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    public function testMinus($a, $b, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->minus($b));
    }

    /**
     * @return array
     */
    public function providerMinus()
    {
        return [
            ['123',    '999',    '-876',   0],
            ['123',    '999.0',  '-8760',  1],
            ['123',    '999.00', '-87600', 2],
            ['123.0',  '999',    '-8760',  1],
            ['123.0',  '999.0',  '-8760',  1],
            ['123.0',  '999.00', '-87600', 2],
            ['123.00', '999',    '-87600', 2],
            ['123.00', '999.0',  '-87600', 2],
            ['123.00', '999.00', '-87600', 2],

            ['123',    '-999',    '1122',   0],
            ['123',    '-999.0',  '11220',  1],
            ['123',    '-999.00', '112200', 2],
            ['123.0',  '-999',    '11220',  1],
            ['123.0',  '-999.0',  '11220',  1],
            ['123.0',  '-999.00', '112200', 2],
            ['123.00', '-999',    '112200', 2],
            ['123.00', '-999.0',  '112200', 2],
            ['123.00', '-999.00', '112200', 2],

            ['-123',    '999',    '-1122',   0],
            ['-123',    '999.0',  '-11220',  1],
            ['-123',    '999.00', '-112200', 2],
            ['-123.0',  '999',    '-11220',  1],
            ['-123.0',  '999.0',  '-11220',  1],
            ['-123.0',  '999.00', '-112200', 2],
            ['-123.00', '999',    '-112200', 2],
            ['-123.00', '999.0',  '-112200', 2],
            ['-123.00', '999.00', '-112200', 2],

            ['-123',    '-999',    '876',   0],
            ['-123',    '-999.0',  '8760',  1],
            ['-123',    '-999.00', '87600', 2],
            ['-123.0',  '-999',    '8760',  1],
            ['-123.0',  '-999.0',  '8760',  1],
            ['-123.0',  '-999.00', '87600', 2],
            ['-123.00', '-999',    '87600', 2],
            ['-123.00', '-999.0',  '87600', 2],
            ['-123.00', '-999.00', '87600', 2],

            ['234878378477428335.3223334343487091', '309049304233536.2392', '2345693291731947990831334343487091', 16],
            ['-2348783784774335.32233343434891', '309049304233536.233392', '-265783308900787155572543434891', 14],
            ['2348783784774335.323232342791', '-309049304233536.556172', '2657833089007871879404342791', 12],
            ['-2348783784774335.3232342791', '-309049304233536.556172', '-20397344805407987670622791', 10],

            ['1234568798347983.2334899238921', '0', '12345687983479832334899238921', 13],
            ['-0.00223287647368738736428467863784', '0.000', '-223287647368738736428467863784', 32],
        ];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param string $a             The base number.
     * @param string $b             The number to multiply.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    public function testMultipliedBy($a, $b, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->multipliedBy($b));
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            ['123',    '999',    '122877',     0],
            ['123',    '999.0',  '1228770',    1],
            ['123',    '999.00', '12287700',   2],
            ['123.0',  '999',    '1228770',    1],
            ['123.0',  '999.0',  '12287700',   2],
            ['123.0',  '999.00', '122877000',  3],
            ['123.00', '999',    '12287700',   2],
            ['123.00', '999.0',  '122877000',  3],
            ['123.00', '999.00', '1228770000', 4],

            ['123',    '-999',    '-122877',     0],
            ['123',    '-999.0',  '-1228770',    1],
            ['123',    '-999.00', '-12287700',   2],
            ['123.0',  '-999',    '-1228770',    1],
            ['123.0',  '-999.0',  '-12287700',   2],
            ['123.0',  '-999.00', '-122877000',  3],
            ['123.00', '-999',    '-12287700',   2],
            ['123.00', '-999.0',  '-122877000',  3],
            ['123.00', '-999.00', '-1228770000', 4],

            ['-123',    '999',    '-122877',     0],
            ['-123',    '999.0',  '-1228770',    1],
            ['-123',    '999.00', '-12287700',   2],
            ['-123.0',  '999',    '-1228770',    1],
            ['-123.0',  '999.0',  '-12287700',   2],
            ['-123.0',  '999.00', '-122877000',  3],
            ['-123.00', '999',    '-12287700',   2],
            ['-123.00', '999.0',  '-122877000',  3],
            ['-123.00', '999.00', '-1228770000', 4],

            ['-123',    '-999',    '122877',     0],
            ['-123',    '-999.0',  '1228770',    1],
            ['-123',    '-999.00', '12287700',   2],
            ['-123.0',  '-999',    '1228770',    1],
            ['-123.0',  '-999.0',  '12287700',   2],
            ['-123.0',  '-999.00', '122877000',  3],
            ['-123.00', '-999',    '12287700',   2],
            ['-123.00', '-999.0',  '122877000',  3],
            ['-123.00', '-999.00', '1228770000', 4],

            ['589252.156111130', '999.2563989942545241223454', '5888139876152080735720775399923986443020', 31],
            ['-589252.15611130', '999.256398994254524122354', '-58881398761537794715991163083004200020', 29],
            ['589252.1561113', '-99.256398994254524122354', '-584870471152079471599116308300420002', 28],
            ['-58952.156111', '-9.256398994254524122357', '545684678534996098129205129273627', 27],

            ['0.1235437849158495728979344999999999999', '1', '1235437849158495728979344999999999999', 37],
            ['-1.324985980890283098409328999999999999', '1', '-1324985980890283098409328999999999999', 36],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param string   $a             The base number.
     * @param string   $b             The number to divide.
     * @param int|null $scale         The desired scale of the result.
     * @param int      $roundingMode  The rounding mode.
     * @param string   $unscaledValue The expected unscaled value of the result.
     * @param int      $expectedScale The expected scale of the result.
     */
    public function testDividedBy($a, $b, $scale, $roundingMode, $unscaledValue, $expectedScale)
    {
        $decimal = BigDecimal::of($a)->dividedBy($b, $scale, $roundingMode);
        $this->assertBigDecimalInternalValues($unscaledValue, $expectedScale, $decimal);
    }

    /**
     * @return array
     */
    public function providerDividedBy()
    {
        return [
            [ '7',  '0.2', 0, RoundingMode::UNNECESSARY,  '35', 0],
            [ '7', '-0.2', 0, RoundingMode::UNNECESSARY, '-35', 0],
            ['-7',  '0.2', 0, RoundingMode::UNNECESSARY, '-35', 0],
            ['-7', '-0.2', 0, RoundingMode::UNNECESSARY,  '35', 0],

            ['1234567890123456789', '0.01', 0,  RoundingMode::UNNECESSARY, '123456789012345678900', 0],
            ['1234567890123456789', '0.010', 0, RoundingMode::UNNECESSARY, '123456789012345678900', 0],

            ['1324794783847839472983.343898', '1', 6, RoundingMode::UNNECESSARY, '1324794783847839472983343898', 6],
            ['-32479478384783947298.3343898', '1', 7, RoundingMode::UNNECESSARY, '-324794783847839472983343898', 7],

            ['1.5', '2', 2, RoundingMode::UNNECESSARY, '75', 2],
            ['1.5', '3', null, RoundingMode::UNNECESSARY, '5', 1],
            ['0.123456789', '0.00244140625', 10, RoundingMode::UNNECESSARY, '505679007744', 10],
            ['1.234', '123.456', 50, RoundingMode::DOWN, '999546397096941420425090720580611715914981855883', 50],
            ['1', '3', 10, RoundingMode::UP, '3333333334', 10],
            ['0.124', '0.2', 3, RoundingMode::UNNECESSARY, '620', 3],
            ['0.124', '2', 3, RoundingMode::UNNECESSARY, '62', 3],
        ];
    }

    /**
     * @dataProvider providerDividedByByZeroThrowsException
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     *
     * @param string|number $zero
     */
    public function testDividedByByZeroThrowsException($zero)
    {
        BigDecimal::of(1)->dividedBy($zero, 0);
    }

    /**
     * @return array
     */
    public function providerDividedByByZeroThrowsException()
    {
        return [
            [0],
            [0.0],
            ['0'],
            ['0.0'],
            ['0.00']
        ];
    }

    /**
     * @dataProvider providerExactlyDividedBy
     *
     * @param string|number $number   The number to divide.
     * @param string|number $divisor  The divisor.
     * @param string        $expected The expected result, or a class name if an exception is expected.
     */
    public function testExactlyDividedBy($number, $divisor, $expected)
    {
        $number = BigDecimal::of($number);

        if ($this->isException($expected)) {
            $this->setExpectedException($expected);
        }

        $actual = $number->exactlyDividedBy($divisor);

        if (! $this->isException($expected)) {
            $this->assertBigDecimalEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerExactlyDividedBy()
    {
        return [
            [1, 1, '1'],
            ['1.0', '1.00', '1'],
            [1, 2, '0.5'],
            [1, 3, RoundingNecessaryException::class],
            [1, 4, '0.25'],
            [1, 5, '0.2'],
            [1, 6, RoundingNecessaryException::class],
            [1, 7, RoundingNecessaryException::class],
            [1, 8, '0.125'],
            [1, 9, RoundingNecessaryException::class],
            [1, 10, '0.1'],
            ['1.0', 2, '0.5'],
            ['1.00', 2, '0.5'],
            ['1.0000', 8, '0.125'],
            [1, '4.000', '0.25'],
            ['1', '0.125', '8'],
            ['1.0', '0.125', '8'],
            ['1234.5678', '2', '617.2839'],
            ['1234.5678', '4', '308.64195'],
            ['1234.5678', '8', '154.320975'],
            ['1234.5678', '6.4', '192.90121875'],
            ['7', '3125', '0.00224'],
            ['4849709849456546549849846510128399', '18014398509481984', '269212976880902984.935786476657271160117801400701864622533321380615234375'],
            ['4849709849456546549849846510128399', '-18014398509481984', '-269212976880902984.935786476657271160117801400701864622533321380615234375'],
            ['-4849709849456546549849846510128399', '18014398509481984', '-269212976880902984.935786476657271160117801400701864622533321380615234375'],
            ['-4849709849456546549849846510128399', '-18014398509481984', '269212976880902984.935786476657271160117801400701864622533321380615234375'],
            ['123', '0', DivisionByZeroException::class],
            [-789, '0.0', DivisionByZeroException::class],
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testExactlyDividedByZero()
    {
        BigDecimal::of(1)->exactlyDividedBy(0);
    }

    /**
     * @dataProvider providerDividedByWithRoundingNecessaryThrowsException
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     *
     * @param string $a     The base number.
     * @param string $b     The number to divide by.
     * @param int    $scale The desired scale,.
     */
    public function testDividedByWithRoundingNecessaryThrowsException($a, $b, $scale)
    {
        BigDecimal::of($a)->dividedBy($b, $scale);
    }

    /**
     * @return array
     */
    public function providerDividedByWithRoundingNecessaryThrowsException()
    {
        return [
            ['1.234', '123.456', 3],
            ['7', '2', 0],
            ['7', '3', 100],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDividedByWithNegativeScaleThrowsException()
    {
        BigDecimal::of(1)->dividedBy(2, -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDividedByWithInvalidRoundingModeThrowsException()
    {
        BigDecimal::of(1)->dividedBy(2, 0, -1);
    }

    /**
     * @dataProvider providerRoundingMode
     *
     * @param int         $roundingMode The rounding mode.
     * @param string      $number       The number to round.
     * @param string|null $two          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null $one          The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null $zero         The expected rounding to a scale of zero, or null if an exception is expected.
     */
    public function testRoundingMode($roundingMode, $number, $two, $one, $zero)
    {
        $number = BigDecimal::of($number);

        $this->doTestRoundingMode($roundingMode, $number, '1', $two, $one, $zero);
        $this->doTestRoundingMode($roundingMode, $number->negated(), '-1', $two, $one, $zero);
    }

    /**
     * @param int         $roundingMode The rounding mode.
     * @param BigDecimal  $number       The number to round.
     * @param string      $divisor      The divisor.
     * @param string|null $two          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null $one          The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null $zero         The expected rounding to a scale of zero, or null if an exception is expected.
     */
    private function doTestRoundingMode($roundingMode, BigDecimal $number, $divisor, $two, $one, $zero)
    {
        foreach ([$zero, $one, $two] as $scale => $expected) {
            if ($expected === null) {
                $this->setExpectedException(RoundingNecessaryException::class);
            }

            $actual = $number->dividedBy($divisor, $scale, $roundingMode);

            if ($expected !== null) {
                $this->assertBigDecimalInternalValues($expected, $scale, $actual);
            }
        }
    }

    /**
     * @return array
     */
    public function providerRoundingMode()
    {
        return [
            [RoundingMode::UP,  '3.501',  '351',  '36',  '4'],
            [RoundingMode::UP,  '3.500',  '350',  '35',  '4'],
            [RoundingMode::UP,  '3.499',  '350',  '35',  '4'],
            [RoundingMode::UP,  '3.001',  '301',  '31',  '4'],
            [RoundingMode::UP,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::UP,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::UP,  '2.501',  '251',  '26',  '3'],
            [RoundingMode::UP,  '2.500',  '250',  '25',  '3'],
            [RoundingMode::UP,  '2.499',  '250',  '25',  '3'],
            [RoundingMode::UP,  '2.001',  '201',  '21',  '3'],
            [RoundingMode::UP,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::UP,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::UP,  '1.501',  '151',  '16',  '2'],
            [RoundingMode::UP,  '1.500',  '150',  '15',  '2'],
            [RoundingMode::UP,  '1.499',  '150',  '15',  '2'],
            [RoundingMode::UP,  '1.001',  '101',  '11',  '2'],
            [RoundingMode::UP,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::UP,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::UP,  '0.501',   '51',   '6',  '1'],
            [RoundingMode::UP,  '0.500',   '50',   '5',  '1'],
            [RoundingMode::UP,  '0.499',   '50',   '5',  '1'],
            [RoundingMode::UP,  '0.001',    '1',   '1',  '1'],
            [RoundingMode::UP,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::UP, '-0.001',   '-1',  '-1', '-1'],
            [RoundingMode::UP, '-0.499',  '-50',  '-5', '-1'],
            [RoundingMode::UP, '-0.500',  '-50',  '-5', '-1'],
            [RoundingMode::UP, '-0.501',  '-51',  '-6', '-1'],
            [RoundingMode::UP, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::UP, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::UP, '-1.001', '-101', '-11', '-2'],
            [RoundingMode::UP, '-1.499', '-150', '-15', '-2'],
            [RoundingMode::UP, '-1.500', '-150', '-15', '-2'],
            [RoundingMode::UP, '-1.501', '-151', '-16', '-2'],
            [RoundingMode::UP, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::UP, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::UP, '-2.001', '-201', '-21', '-3'],
            [RoundingMode::UP, '-2.499', '-250', '-25', '-3'],
            [RoundingMode::UP, '-2.500', '-250', '-25', '-3'],
            [RoundingMode::UP, '-2.501', '-251', '-26', '-3'],
            [RoundingMode::UP, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::UP, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::UP, '-3.001', '-301', '-31', '-4'],
            [RoundingMode::UP, '-3.499', '-350', '-35', '-4'],
            [RoundingMode::UP, '-3.500', '-350', '-35', '-4'],
            [RoundingMode::UP, '-3.501', '-351', '-36', '-4'],

            [RoundingMode::DOWN,  '3.501',  '350',  '35',  '3'],
            [RoundingMode::DOWN,  '3.500',  '350',  '35',  '3'],
            [RoundingMode::DOWN,  '3.499',  '349',  '34',  '3'],
            [RoundingMode::DOWN,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::DOWN,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::DOWN,  '2.999',  '299',  '29',  '2'],
            [RoundingMode::DOWN,  '2.501',  '250',  '25',  '2'],
            [RoundingMode::DOWN,  '2.500',  '250',  '25',  '2'],
            [RoundingMode::DOWN,  '2.499',  '249',  '24',  '2'],
            [RoundingMode::DOWN,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::DOWN,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::DOWN,  '1.999',  '199',  '19',  '1'],
            [RoundingMode::DOWN,  '1.501',  '150',  '15',  '1'],
            [RoundingMode::DOWN,  '1.500',  '150',  '15',  '1'],
            [RoundingMode::DOWN,  '1.499',  '149',  '14',  '1'],
            [RoundingMode::DOWN,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::DOWN,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::DOWN,  '0.999',   '99',   '9',  '0'],
            [RoundingMode::DOWN,  '0.501',   '50',   '5',  '0'],
            [RoundingMode::DOWN,  '0.500',   '50',   '5',  '0'],
            [RoundingMode::DOWN,  '0.499',   '49',   '4',  '0'],
            [RoundingMode::DOWN,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::DOWN,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::DOWN, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::DOWN, '-0.499',  '-49',  '-4',  '0'],
            [RoundingMode::DOWN, '-0.500',  '-50',  '-5',  '0'],
            [RoundingMode::DOWN, '-0.501',  '-50',  '-5',  '0'],
            [RoundingMode::DOWN, '-0.999',  '-99',  '-9',  '0'],
            [RoundingMode::DOWN, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::DOWN, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::DOWN, '-1.499', '-149', '-14', '-1'],
            [RoundingMode::DOWN, '-1.500', '-150', '-15', '-1'],
            [RoundingMode::DOWN, '-1.501', '-150', '-15', '-1'],
            [RoundingMode::DOWN, '-1.999', '-199', '-19', '-1'],
            [RoundingMode::DOWN, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::DOWN, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::DOWN, '-2.499', '-249', '-24', '-2'],
            [RoundingMode::DOWN, '-2.500', '-250', '-25', '-2'],
            [RoundingMode::DOWN, '-2.501', '-250', '-25', '-2'],
            [RoundingMode::DOWN, '-2.999', '-299', '-29', '-2'],
            [RoundingMode::DOWN, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::DOWN, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::DOWN, '-3.499', '-349', '-34', '-3'],
            [RoundingMode::DOWN, '-3.500', '-350', '-35', '-3'],
            [RoundingMode::DOWN, '-3.501', '-350', '-35', '-3'],

            [RoundingMode::CEILING,  '3.501',  '351',  '36',  '4'],
            [RoundingMode::CEILING,  '3.500',  '350',  '35',  '4'],
            [RoundingMode::CEILING,  '3.499',  '350',  '35',  '4'],
            [RoundingMode::CEILING,  '3.001',  '301',  '31',  '4'],
            [RoundingMode::CEILING,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::CEILING,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::CEILING,  '2.501',  '251',  '26',  '3'],
            [RoundingMode::CEILING,  '2.500',  '250',  '25',  '3'],
            [RoundingMode::CEILING,  '2.499',  '250',  '25',  '3'],
            [RoundingMode::CEILING,  '2.001',  '201',  '21',  '3'],
            [RoundingMode::CEILING,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::CEILING,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::CEILING,  '1.501',  '151',  '16',  '2'],
            [RoundingMode::CEILING,  '1.500',  '150',  '15',  '2'],
            [RoundingMode::CEILING,  '1.499',  '150',  '15',  '2'],
            [RoundingMode::CEILING,  '1.001',  '101',  '11',  '2'],
            [RoundingMode::CEILING,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::CEILING,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::CEILING,  '0.501',   '51',   '6',  '1'],
            [RoundingMode::CEILING,  '0.500',   '50',   '5',  '1'],
            [RoundingMode::CEILING,  '0.499',   '50',   '5',  '1'],
            [RoundingMode::CEILING,  '0.001',    '1',   '1',  '1'],
            [RoundingMode::CEILING,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::CEILING, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::CEILING, '-0.499',  '-49' , '-4',  '0'],
            [RoundingMode::CEILING, '-0.500',  '-50' , '-5',  '0'],
            [RoundingMode::CEILING, '-0.501',  '-50',  '-5',  '0'],
            [RoundingMode::CEILING, '-0.999',  '-99',  '-9',  '0'],
            [RoundingMode::CEILING, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::CEILING, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::CEILING, '-1.499', '-149', '-14', '-1'],
            [RoundingMode::CEILING, '-1.500', '-150', '-15', '-1'],
            [RoundingMode::CEILING, '-1.501', '-150', '-15', '-1'],
            [RoundingMode::CEILING, '-1.999', '-199', '-19', '-1'],
            [RoundingMode::CEILING, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::CEILING, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::CEILING, '-2.499', '-249', '-24', '-2'],
            [RoundingMode::CEILING, '-2.500', '-250', '-25', '-2'],
            [RoundingMode::CEILING, '-2.501', '-250', '-25', '-2'],
            [RoundingMode::CEILING, '-2.999', '-299', '-29', '-2'],
            [RoundingMode::CEILING, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::CEILING, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::CEILING, '-3.499', '-349', '-34', '-3'],
            [RoundingMode::CEILING, '-3.500', '-350', '-35', '-3'],
            [RoundingMode::CEILING, '-3.501', '-350', '-35', '-3'],

            [RoundingMode::FLOOR,  '3.501',  '350',  '35',  '3'],
            [RoundingMode::FLOOR,  '3.500',  '350',  '35',  '3'],
            [RoundingMode::FLOOR,  '3.499',  '349',  '34',  '3'],
            [RoundingMode::FLOOR,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::FLOOR,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::FLOOR,  '2.999',  '299',  '29',  '2'],
            [RoundingMode::FLOOR,  '2.501',  '250',  '25',  '2'],
            [RoundingMode::FLOOR,  '2.500',  '250',  '25',  '2'],
            [RoundingMode::FLOOR,  '2.499',  '249',  '24',  '2'],
            [RoundingMode::FLOOR,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::FLOOR,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::FLOOR,  '1.999',  '199',  '19',  '1'],
            [RoundingMode::FLOOR,  '1.501',  '150',  '15',  '1'],
            [RoundingMode::FLOOR,  '1.500',  '150',  '15',  '1'],
            [RoundingMode::FLOOR,  '1.499',  '149',  '14',  '1'],
            [RoundingMode::FLOOR,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::FLOOR,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::FLOOR,  '0.999',   '99',   '9',  '0'],
            [RoundingMode::FLOOR,  '0.501',   '50',   '5',  '0'],
            [RoundingMode::FLOOR,  '0.500',   '50',   '5',  '0'],
            [RoundingMode::FLOOR,  '0.499',   '49',   '4',  '0'],
            [RoundingMode::FLOOR,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::FLOOR,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::FLOOR, '-0.001',   '-1',  '-1', '-1'],
            [RoundingMode::FLOOR, '-0.499',  '-50',  '-5', '-1'],
            [RoundingMode::FLOOR, '-0.500',  '-50',  '-5', '-1'],
            [RoundingMode::FLOOR, '-0.501',  '-51',  '-6', '-1'],
            [RoundingMode::FLOOR, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::FLOOR, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::FLOOR, '-1.001', '-101', '-11', '-2'],
            [RoundingMode::FLOOR, '-1.499', '-150', '-15', '-2'],
            [RoundingMode::FLOOR, '-1.500', '-150', '-15', '-2'],
            [RoundingMode::FLOOR, '-1.501', '-151', '-16', '-2'],
            [RoundingMode::FLOOR, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::FLOOR, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::FLOOR, '-2.001', '-201', '-21', '-3'],
            [RoundingMode::FLOOR, '-2.499', '-250', '-25', '-3'],
            [RoundingMode::FLOOR, '-2.500', '-250', '-25', '-3'],
            [RoundingMode::FLOOR, '-2.501', '-251', '-26', '-3'],
            [RoundingMode::FLOOR, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::FLOOR, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::FLOOR, '-3.001', '-301', '-31', '-4'],
            [RoundingMode::FLOOR, '-3.499', '-350', '-35', '-4'],
            [RoundingMode::FLOOR, '-3.500', '-350', '-35', '-4'],
            [RoundingMode::FLOOR, '-3.501', '-351', '-36', '-4'],

            [RoundingMode::HALF_UP,  '3.501',  '350',  '35',  '4'],
            [RoundingMode::HALF_UP,  '3.500',  '350',  '35',  '4'],
            [RoundingMode::HALF_UP,  '3.499',  '350',  '35',  '3'],
            [RoundingMode::HALF_UP,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::HALF_UP,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::HALF_UP,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::HALF_UP,  '2.501',  '250',  '25',  '3'],
            [RoundingMode::HALF_UP,  '2.500',  '250',  '25',  '3'],
            [RoundingMode::HALF_UP,  '2.499',  '250',  '25',  '2'],
            [RoundingMode::HALF_UP,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::HALF_UP,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::HALF_UP,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::HALF_UP,  '1.501',  '150',  '15',  '2'],
            [RoundingMode::HALF_UP,  '1.500',  '150',  '15',  '2'],
            [RoundingMode::HALF_UP,  '1.499',  '150',  '15',  '1'],
            [RoundingMode::HALF_UP,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::HALF_UP,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::HALF_UP,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::HALF_UP,  '0.501',   '50',   '5',  '1'],
            [RoundingMode::HALF_UP,  '0.500',   '50',   '5',  '1'],
            [RoundingMode::HALF_UP,  '0.499',   '50',   '5',  '0'],
            [RoundingMode::HALF_UP,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_UP,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::HALF_UP, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_UP, '-0.499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_UP, '-0.500',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_UP, '-0.501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_UP, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::HALF_UP, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::HALF_UP, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::HALF_UP, '-1.499', '-150', '-15', '-1'],
            [RoundingMode::HALF_UP, '-1.500', '-150', '-15', '-2'],
            [RoundingMode::HALF_UP, '-1.501', '-150', '-15', '-2'],
            [RoundingMode::HALF_UP, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::HALF_UP, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::HALF_UP, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::HALF_UP, '-2.499', '-250', '-25', '-2'],
            [RoundingMode::HALF_UP, '-2.500', '-250', '-25', '-3'],
            [RoundingMode::HALF_UP, '-2.501', '-250', '-25', '-3'],
            [RoundingMode::HALF_UP, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::HALF_UP, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::HALF_UP, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::HALF_UP, '-3.499', '-350', '-35', '-3'],
            [RoundingMode::HALF_UP, '-3.500', '-350', '-35', '-4'],
            [RoundingMode::HALF_UP, '-3.501', '-350', '-35', '-4'],

            [RoundingMode::HALF_DOWN,  '3.501',  '350',  '35',  '4'],
            [RoundingMode::HALF_DOWN,  '3.500',  '350',  '35',  '3'],
            [RoundingMode::HALF_DOWN,  '3.499',  '350',  '35',  '3'],
            [RoundingMode::HALF_DOWN,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::HALF_DOWN,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::HALF_DOWN,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::HALF_DOWN,  '2.501',  '250',  '25',  '3'],
            [RoundingMode::HALF_DOWN,  '2.500',  '250',  '25',  '2'],
            [RoundingMode::HALF_DOWN,  '2.499',  '250',  '25',  '2'],
            [RoundingMode::HALF_DOWN,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::HALF_DOWN,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::HALF_DOWN,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::HALF_DOWN,  '1.501',  '150',  '15',  '2'],
            [RoundingMode::HALF_DOWN,  '1.500',  '150',  '15',  '1'],
            [RoundingMode::HALF_DOWN,  '1.499',  '150',  '15',  '1'],
            [RoundingMode::HALF_DOWN,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::HALF_DOWN,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::HALF_DOWN,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::HALF_DOWN,  '0.501',   '50',   '5',  '1'],
            [RoundingMode::HALF_DOWN,  '0.500',   '50',   '5',  '0'],
            [RoundingMode::HALF_DOWN,  '0.499',   '50',   '5',  '0'],
            [RoundingMode::HALF_DOWN,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_DOWN,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::HALF_DOWN, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_DOWN, '-0.499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_DOWN, '-0.500',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_DOWN, '-0.501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_DOWN, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::HALF_DOWN, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::HALF_DOWN, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::HALF_DOWN, '-1.499', '-150', '-15', '-1'],
            [RoundingMode::HALF_DOWN, '-1.500', '-150', '-15', '-1'],
            [RoundingMode::HALF_DOWN, '-1.501', '-150', '-15', '-2'],
            [RoundingMode::HALF_DOWN, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::HALF_DOWN, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::HALF_DOWN, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::HALF_DOWN, '-2.499', '-250', '-25', '-2'],
            [RoundingMode::HALF_DOWN, '-2.500', '-250', '-25', '-2'],
            [RoundingMode::HALF_DOWN, '-2.501', '-250', '-25', '-3'],
            [RoundingMode::HALF_DOWN, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::HALF_DOWN, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::HALF_DOWN, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::HALF_DOWN, '-3.499', '-350', '-35', '-3'],
            [RoundingMode::HALF_DOWN, '-3.500', '-350', '-35', '-3'],
            [RoundingMode::HALF_DOWN, '-3.501', '-350', '-35', '-4'],

            [RoundingMode::HALF_CEILING,  '3.501',  '350',  '35',  '4'],
            [RoundingMode::HALF_CEILING,  '3.500',  '350',  '35',  '4'],
            [RoundingMode::HALF_CEILING,  '3.499',  '350',  '35',  '3'],
            [RoundingMode::HALF_CEILING,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::HALF_CEILING,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::HALF_CEILING,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::HALF_CEILING,  '2.501',  '250',  '25',  '3'],
            [RoundingMode::HALF_CEILING,  '2.500',  '250',  '25',  '3'],
            [RoundingMode::HALF_CEILING,  '2.499',  '250',  '25',  '2'],
            [RoundingMode::HALF_CEILING,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::HALF_CEILING,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::HALF_CEILING,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::HALF_CEILING,  '1.501',  '150',  '15',  '2'],
            [RoundingMode::HALF_CEILING,  '1.500',  '150',  '15',  '2'],
            [RoundingMode::HALF_CEILING,  '1.499',  '150',  '15',  '1'],
            [RoundingMode::HALF_CEILING,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::HALF_CEILING,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::HALF_CEILING,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::HALF_CEILING,  '0.501',   '50',   '5',  '1'],
            [RoundingMode::HALF_CEILING,  '0.500',   '50',   '5',  '1'],
            [RoundingMode::HALF_CEILING,  '0.499',   '50',   '5',  '0'],
            [RoundingMode::HALF_CEILING,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_CEILING,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::HALF_CEILING, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_CEILING, '-0.499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_CEILING, '-0.500',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_CEILING, '-0.501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_CEILING, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::HALF_CEILING, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::HALF_CEILING, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::HALF_CEILING, '-1.499', '-150', '-15', '-1'],
            [RoundingMode::HALF_CEILING, '-1.500', '-150', '-15', '-1'],
            [RoundingMode::HALF_CEILING, '-1.501', '-150', '-15', '-2'],
            [RoundingMode::HALF_CEILING, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::HALF_CEILING, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::HALF_CEILING, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::HALF_CEILING, '-2.499', '-250', '-25', '-2'],
            [RoundingMode::HALF_CEILING, '-2.500', '-250', '-25', '-2'],
            [RoundingMode::HALF_CEILING, '-2.501', '-250', '-25', '-3'],
            [RoundingMode::HALF_CEILING, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::HALF_CEILING, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::HALF_CEILING, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::HALF_CEILING, '-3.499', '-350', '-35', '-3'],
            [RoundingMode::HALF_CEILING, '-3.500', '-350', '-35', '-3'],
            [RoundingMode::HALF_CEILING, '-3.501', '-350', '-35', '-4'],

            [RoundingMode::HALF_FLOOR,  '3.501',  '350',  '35',  '4'],
            [RoundingMode::HALF_FLOOR,  '3.500',  '350',  '35',  '3'],
            [RoundingMode::HALF_FLOOR,  '3.499',  '350',  '35',  '3'],
            [RoundingMode::HALF_FLOOR,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::HALF_FLOOR,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::HALF_FLOOR,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::HALF_FLOOR,  '2.501',  '250',  '25',  '3'],
            [RoundingMode::HALF_FLOOR,  '2.500',  '250',  '25',  '2'],
            [RoundingMode::HALF_FLOOR,  '2.499',  '250',  '25',  '2'],
            [RoundingMode::HALF_FLOOR,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::HALF_FLOOR,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::HALF_FLOOR,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::HALF_FLOOR,  '1.501',  '150',  '15',  '2'],
            [RoundingMode::HALF_FLOOR,  '1.500',  '150',  '15',  '1'],
            [RoundingMode::HALF_FLOOR,  '1.499',  '150',  '15',  '1'],
            [RoundingMode::HALF_FLOOR,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::HALF_FLOOR,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::HALF_FLOOR,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::HALF_FLOOR,  '0.501',   '50',   '5',  '1'],
            [RoundingMode::HALF_FLOOR,  '0.500',   '50',   '5',  '0'],
            [RoundingMode::HALF_FLOOR,  '0.499',   '50',   '5',  '0'],
            [RoundingMode::HALF_FLOOR,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_FLOOR,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::HALF_FLOOR, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_FLOOR, '-0.499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_FLOOR, '-0.500',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_FLOOR, '-0.501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_FLOOR, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::HALF_FLOOR, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::HALF_FLOOR, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::HALF_FLOOR, '-1.499', '-150', '-15', '-1'],
            [RoundingMode::HALF_FLOOR, '-1.500', '-150', '-15', '-2'],
            [RoundingMode::HALF_FLOOR, '-1.501', '-150', '-15', '-2'],
            [RoundingMode::HALF_FLOOR, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::HALF_FLOOR, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::HALF_FLOOR, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::HALF_FLOOR, '-2.499', '-250', '-25', '-2'],
            [RoundingMode::HALF_FLOOR, '-2.500', '-250', '-25', '-3'],
            [RoundingMode::HALF_FLOOR, '-2.501', '-250', '-25', '-3'],
            [RoundingMode::HALF_FLOOR, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::HALF_FLOOR, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::HALF_FLOOR, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::HALF_FLOOR, '-3.499', '-350', '-35', '-3'],
            [RoundingMode::HALF_FLOOR, '-3.500', '-350', '-35', '-4'],
            [RoundingMode::HALF_FLOOR, '-3.501', '-350', '-35', '-4'],

            [RoundingMode::HALF_EVEN,  '3.501',  '350',  '35',  '4'],
            [RoundingMode::HALF_EVEN,  '3.500',  '350',  '35',  '4'],
            [RoundingMode::HALF_EVEN,  '3.499',  '350',  '35',  '3'],
            [RoundingMode::HALF_EVEN,  '3.001',  '300',  '30',  '3'],
            [RoundingMode::HALF_EVEN,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::HALF_EVEN,  '2.999',  '300',  '30',  '3'],
            [RoundingMode::HALF_EVEN,  '2.501',  '250',  '25',  '3'],
            [RoundingMode::HALF_EVEN,  '2.500',  '250',  '25',  '2'],
            [RoundingMode::HALF_EVEN,  '2.499',  '250',  '25',  '2'],
            [RoundingMode::HALF_EVEN,  '2.001',  '200',  '20',  '2'],
            [RoundingMode::HALF_EVEN,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::HALF_EVEN,  '1.999',  '200',  '20',  '2'],
            [RoundingMode::HALF_EVEN,  '1.501',  '150',  '15',  '2'],
            [RoundingMode::HALF_EVEN,  '1.500',  '150',  '15',  '2'],
            [RoundingMode::HALF_EVEN,  '1.499',  '150',  '15',  '1'],
            [RoundingMode::HALF_EVEN,  '1.001',  '100',  '10',  '1'],
            [RoundingMode::HALF_EVEN,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::HALF_EVEN,  '0.999',  '100',  '10',  '1'],
            [RoundingMode::HALF_EVEN,  '0.501',   '50',   '5',  '1'],
            [RoundingMode::HALF_EVEN,  '0.500',   '50',   '5',  '0'],
            [RoundingMode::HALF_EVEN,  '0.499',   '50',   '5',  '0'],
            [RoundingMode::HALF_EVEN,  '0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_EVEN,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::HALF_EVEN, '-0.001',    '0',   '0',  '0'],
            [RoundingMode::HALF_EVEN, '-0.499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_EVEN, '-0.500',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_EVEN, '-0.501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_EVEN, '-0.999', '-100', '-10', '-1'],
            [RoundingMode::HALF_EVEN, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::HALF_EVEN, '-1.001', '-100', '-10', '-1'],
            [RoundingMode::HALF_EVEN, '-1.499', '-150', '-15', '-1'],
            [RoundingMode::HALF_EVEN, '-1.500', '-150', '-15', '-2'],
            [RoundingMode::HALF_EVEN, '-1.501', '-150', '-15', '-2'],
            [RoundingMode::HALF_EVEN, '-1.999', '-200', '-20', '-2'],
            [RoundingMode::HALF_EVEN, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::HALF_EVEN, '-2.001', '-200', '-20', '-2'],
            [RoundingMode::HALF_EVEN, '-2.499', '-250', '-25', '-2'],
            [RoundingMode::HALF_EVEN, '-2.500', '-250', '-25', '-2'],
            [RoundingMode::HALF_EVEN, '-2.501', '-250', '-25', '-3'],
            [RoundingMode::HALF_EVEN, '-2.999', '-300', '-30', '-3'],
            [RoundingMode::HALF_EVEN, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::HALF_EVEN, '-3.001', '-300', '-30', '-3'],
            [RoundingMode::HALF_EVEN, '-3.499', '-350', '-35', '-3'],
            [RoundingMode::HALF_EVEN, '-3.500', '-350', '-35', '-4'],
            [RoundingMode::HALF_EVEN, '-3.501', '-350', '-35', '-4'],

            [RoundingMode::UNNECESSARY,  '3.501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '3.500',  '350',  '35', null],
            [RoundingMode::UNNECESSARY,  '3.499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '3.001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '3.000',  '300',  '30',  '3'],
            [RoundingMode::UNNECESSARY,  '2.999',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2.501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2.500',  '250',  '25', null],
            [RoundingMode::UNNECESSARY,  '2.499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2.001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2.000',  '200',  '20',  '2'],
            [RoundingMode::UNNECESSARY,  '1.999',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1.501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1.500',  '150',  '15', null],
            [RoundingMode::UNNECESSARY,  '1.499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1.001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1.000',  '100',  '10',  '1'],
            [RoundingMode::UNNECESSARY,  '0.999',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '0.501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '0.500',   '50',   '5', null],
            [RoundingMode::UNNECESSARY,  '0.499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '0.001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '0.000',    '0',   '0',  '0'],
            [RoundingMode::UNNECESSARY, '-0.001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-0.499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-0.500',  '-50',  '-5', null],
            [RoundingMode::UNNECESSARY, '-0.501',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-0.999',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1.000', '-100', '-10', '-1'],
            [RoundingMode::UNNECESSARY, '-1.001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1.499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1.500', '-150', '-15', null],
            [RoundingMode::UNNECESSARY, '-1.501',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1.999',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2.000', '-200', '-20', '-2'],
            [RoundingMode::UNNECESSARY, '-2.001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2.499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2.500', '-250', '-25', null],
            [RoundingMode::UNNECESSARY, '-2.501',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2.999',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-3.000', '-300', '-30', '-3'],
            [RoundingMode::UNNECESSARY, '-3.001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-3.499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-3.500', '-350', '-35', null],
            [RoundingMode::UNNECESSARY, '-3.501',   null,  null, null],
        ];
    }

    /**
     * @dataProvider providerQuotientAndRemainder
     *
     * @param string $dividend  The dividend.
     * @param string $divisor   The divisor.
     * @param string $quotient  The expected quotient.
     * @param string $remainder The expected remainder.
     */
    public function testQuotientAndRemainder($dividend, $divisor, $quotient, $remainder)
    {
        $dividend = BigDecimal::of($dividend);

        $this->assertBigDecimalEquals($quotient, $dividend->quotient($divisor));
        $this->assertBigDecimalEquals($remainder, $dividend->remainder($divisor));

        list ($q, $r) = $dividend->quotientAndRemainder($divisor);

        $this->assertBigDecimalEquals($quotient, $q);
        $this->assertBigDecimalEquals($remainder, $r);
    }

    /**
     * @return array
     */
    public function providerQuotientAndRemainder()
    {
        return [
            ['1', '123', '0', '1'],
            ['1', '-123', '0', '1'],
            ['-1', '123', '0', '-1'],
            ['-1', '-123', '0', '-1'],

            ['1999999999999999999999999', '2000000000000000000000000', '0', '1999999999999999999999999'],
            ['1999999999999999999999999', '-2000000000000000000000000', '0', '1999999999999999999999999'],
            ['-1999999999999999999999999', '2000000000000000000000000', '0', '-1999999999999999999999999'],
            ['-1999999999999999999999999', '-2000000000000000000000000', '0', '-1999999999999999999999999'],

            ['123', '1', '123', '0'],
            ['123', '-1', '-123', '0'],
            ['-123', '1', '-123', '0'],
            ['-123', '-1', '123', '0'],

            ['123', '2', '61', '1'],
            ['123', '-2', '-61', '1'],
            ['-123', '2', '-61', '-1'],
            ['-123', '-2', '61', '-1'],

            ['123', '123', '1', '0'],
            ['123', '-123', '-1', '0'],
            ['-123', '123', '-1', '0'],
            ['-123', '-123', '1', '0'],

            ['123', '124', '0', '123'],
            ['123', '-124', '0', '123'],
            ['-123', '124', '0', '-123'],
            ['-123', '-124', '0', '-123'],

            ['124', '123', '1', '1'],
            ['124', '-123', '-1', '1'],
            ['-124', '123', '-1', '-1'],
            ['-124', '-123', '1', '-1'],

            ['1000000000000000000000000000000', '3', '333333333333333333333333333333', '1'],
            ['1000000000000000000000000000000', '9', '111111111111111111111111111111', '1'],
            ['1000000000000000000000000000000', '11', '90909090909090909090909090909', '1'],
            ['1000000000000000000000000000000', '13', '76923076923076923076923076923', '1'],
            ['1000000000000000000000000000000', '21', '47619047619047619047619047619', '1'],

            ['123456789123456789123456789', '987654321987654321', '124999998', '850308642973765431'],
            ['123456789123456789123456789', '-87654321987654321', '-1408450676', '65623397056685793'],
            ['-123456789123456789123456789', '7654321987654321', '-16129030020', '-1834176331740369'],
            ['-123456789123456789123456789', '-654321987654321', '188678955396', '-205094497790673'],

            ['10.11', '3.3', '3', '0.21'],
            ['1', '-0.0013', '-769', '0.0003'],
            ['-1.000000000000000000001', '0.0000009298439898981609', '-1075449', '-0.0000002109080127582569'],
            ['-1278438782896060000132323.32333', '-53.4836775545640521556878910541', '23903344746475158719036', '-30.0786684482104867175202241524'],
            ['23999593472872987498347103908209387429846376', '-0.005', '-4799918694574597499669420781641877485969275200', '0.000'],

            ['1000000000000000000000000000000.0', '3', '333333333333333333333333333333', '1.0'],
            ['1000000000000000000000000000000.0', '9', '111111111111111111111111111111', '1.0'],
            ['1000000000000000000000000000000.0', '11', '90909090909090909090909090909', '1.0'],
            ['1000000000000000000000000000000.0', '13', '76923076923076923076923076923', '1.0'],
            ['0.9999999999999999999999999999999', '0.21', '4', '0.1599999999999999999999999999999'],

            ['1000000000000000000000000000000.0', '3.9', '256410256410256410256410256410', '1.0'],
            ['-1000000000000000000000000000000.0', '9.8', '-102040816326530612244897959183', '-6.6'],
            ['1000000000000000000000000000000.0', '-11.7', '-85470085470085470085470085470', '1.0'],
            ['-1000000000000000000000000000000.0', '-13.7', '72992700729927007299270072992', '-9.6'],
            ['0.99999999999999999999999999999999', '0.215', '4', '0.13999999999999999999999999999999'],
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testQuotientOfZeroThrowsException()
    {
        BigDecimal::of(1.2)->quotient(0);
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testRemainderOfZeroThrowsException()
    {
        BigDecimal::of(1.2)->remainder(0);
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testQuotientAndRemainderOfZeroThrowsException()
    {
        BigDecimal::of(1.2)->quotientAndRemainder(0);
    }

    /**
     * @dataProvider providerPower
     *
     * @param string  $number        The base number.
     * @param int     $exponent      The exponent to apply.
     * @param string  $unscaledValue The expected unscaled value of the result.
     * @param int     $scale         The expected scale of the result.
     */
    public function testPower($number, $exponent, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->power($exponent));
    }

    /**
     * @return array
     */
    public function providerPower()
    {
        return [
            ['-3', 0, '1', 0],
            ['-2', 0, '1', 0],
            ['-1', 0, '1', 0],
            ['0',  0, '1', 0],
            ['1',  0, '1', 0],
            ['2',  0, '1', 0],
            ['3',  0, '1', 0],

            ['-3', 1, '-3', 0],
            ['-2', 1, '-2', 0],
            ['-1', 1, '-1', 0],
            ['0',  1,  '0', 0],
            ['1',  1,  '1', 0],
            ['2',  1,  '2', 0],
            ['3',  1,  '3', 0],

            ['-3', 2, '9', 0],
            ['-2', 2, '4', 0],
            ['-1', 2, '1', 0],
            ['0',  2, '0', 0],
            ['1',  2, '1', 0],
            ['2',  2, '4', 0],
            ['3',  2, '9', 0],

            ['-3', 3, '-27', 0],
            ['-2', 3,  '-8', 0],
            ['-1', 3,  '-1', 0],
            ['0',  3,   '0', 0],
            ['1',  3,   '1', 0],
            ['2',  3,   '8', 0],
            ['3',  3,  '27', 0],

            ['0', 1000000, '0', 0],
            ['1', 1000000, '1', 0],

            ['-2', 255, '-57896044618658097711785492504343953926634992332820282019728792003956564819968', 0],
            [ '2', 256, '115792089237316195423570985008687907853269984665640564039457584007913129639936', 0],

            ['-1.23', 0, '1', 0],
            ['-1.23', 0, '1', 0],
            ['-1.23', 33, '-926549609804623448265268294182900512918058893428212027689876489708283', 66],
            [ '1.23', 34, '113965602005968684136628000184496763088921243891670079405854808234118809', 68],

            ['-123456789', 8, '53965948844821664748141453212125737955899777414752273389058576481', 0],
            ['9876543210', 7, '9167159269868350921847491739460569765344716959834325922131706410000000', 0]
        ];
    }

    /**
     * @dataProvider providerPowerWithInvalidExponentThrowsException
     * @expectedException \InvalidArgumentException
     *
     * @param int $power
     */
    public function testPowerWithInvalidExponentThrowsException($power)
    {
        BigDecimal::of(1)->power($power);
    }

    /**
     * @return array
     */
    public function providerPowerWithInvalidExponentThrowsException()
    {
        return [
            [-1],
            [1000001]
        ];
    }

    /**
     * @dataProvider withScaleProvider
     *
     * @param string $number        The number to scale.
     * @param int    $withScale     The scale to apply.
     * @param int    $roundingMode  The rounding mode to apply.
     * @param string $unscaledValue The expected unscaled value of the result.
     * @param int    $scale         The expected scale of the result.
     */
    public function testWithScale($number, $withScale, $roundingMode, $unscaledValue, $scale)
    {
        $decimal = BigDecimal::of($number)->withScale($withScale, $roundingMode);
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, $decimal);
    }

    /**
     * @return array
     */
    public function withScaleProvider()
    {
        return [
            ['123.45', 0, RoundingMode::DOWN, '123', 0],
            ['123.45', 1, RoundingMode::UP, '1235', 1],
            ['123.45', 2, RoundingMode::UNNECESSARY, '12345', 2],
            ['123.45', 5, RoundingMode::UNNECESSARY, '12345000', 5]
        ];
    }

    /**
     * @dataProvider providerWithPointMovedLeft
     *
     * @param string $number   The decimal number as a string.
     * @param int    $places   The number of decimal places to move left.
     * @param string $expected The expected result.
     */
    public function testWithPointMovedLeft($number, $places, $expected)
    {
        $this->assertBigDecimalEquals($expected, BigDecimal::of($number)->withPointMovedLeft($places));
    }

    /**
     * @return array
     */
    public function providerWithPointMovedLeft()
    {
        return [
            ['0', -2, '0'],
            ['0', -1, '0'],
            ['0', 0, '0'],
            ['0', 1, '0.0'],
            ['0', 2, '0.00'],

            ['0.0', -2, '0'],
            ['0.0', -1, '0'],
            ['0.0', 0, '0.0'],
            ['0.0', 1, '0.00'],
            ['0.0', 2, '0.000'],

            ['1', -2, '100'],
            ['1', -1, '10'],
            ['1', 0, '1'],
            ['1', 1, '0.1'],
            ['1', 2, '0.01'],

            ['12', -2, '1200'],
            ['12', -1, '120'],
            ['12', 0, '12'],
            ['12', 1, '1.2'],
            ['12', 2, '0.12'],

            ['1.1', -2, '110'],
            ['1.1', -1, '11'],
            ['1.1', 0, '1.1'],
            ['1.1', 1, '0.11'],
            ['1.1', 2, '0.011'],

            ['0.1', -2, '10'],
            ['0.1', -1, '1'],
            ['0.1', 0, '0.1'],
            ['0.1', 1, '0.01'],
            ['0.1', 2, '0.001'],

            ['0.01', -2, '1'],
            ['0.01', -1, '0.1'],
            ['0.01', 0, '0.01'],
            ['0.01', 1, '0.001'],
            ['0.01', 2, '0.0001'],

            ['-9', -2, '-900'],
            ['-9', -1, '-90'],
            ['-9', 0, '-9'],
            ['-9', 1, '-0.9'],
            ['-9', 2, '-0.09'],

            ['-0.9', -2, '-90'],
            ['-0.9', -1, '-9'],
            ['-0.9', 0, '-0.9'],
            ['-0.9', 1, '-0.09'],
            ['-0.9', 2, '-0.009'],

            ['-0.09', -2, '-9'],
            ['-0.09', -1, '-0.9'],
            ['-0.09', 0, '-0.09'],
            ['-0.09', 1, '-0.009'],
            ['-0.09', 2, '-0.0009'],

            ['-12.3', -2, '-1230'],
            ['-12.3', -1, '-123'],
            ['-12.3', 0, '-12.3'],
            ['-12.3', 1, '-1.23'],
            ['-12.3', 2, '-0.123'],
        ];
    }

    /**
     * @dataProvider providerWithPointMovedRight
     *
     * @param string $number   The decimal number as a string.
     * @param int    $places   The number of decimal places to move right.
     * @param string $expected The expected result.
     */
    public function testWithPointMovedRight($number, $places, $expected)
    {
        $this->assertBigDecimalEquals($expected, BigDecimal::of($number)->withPointMovedRight($places));
    }

    /**
     * @return array
     */
    public function providerWithPointMovedRight()
    {
        return [
            ['0', -2, '0.00'],
            ['0', -1, '0.0'],
            ['0', 0, '0'],
            ['0', 1, '0'],
            ['0', 2, '0'],

            ['0.0', -2, '0.000'],
            ['0.0', -1, '0.00'],
            ['0.0', 0, '0.0'],
            ['0.0', 1, '0'],
            ['0.0', 2, '0'],

            ['9', -2, '0.09'],
            ['9', -1, '0.9'],
            ['9', 0, '9'],
            ['9', 1, '90'],
            ['9', 2, '900'],

            ['89', -2, '0.89'],
            ['89', -1, '8.9'],
            ['89', 0, '89'],
            ['89', 1, '890'],
            ['89', 2, '8900'],

            ['8.9', -2, '0.089'],
            ['8.9', -1, '0.89'],
            ['8.9', 0, '8.9'],
            ['8.9', 1, '89'],
            ['8.9', 2, '890'],

            ['0.9', -2, '0.009'],
            ['0.9', -1, '0.09'],
            ['0.9', 0, '0.9'],
            ['0.9', 1, '9'],
            ['0.9', 2, '90'],

            ['0.09', -2, '0.0009'],
            ['0.09', -1, '0.009'],
            ['0.09', 0, '0.09'],
            ['0.09', 1, '0.9'],
            ['0.09', 2, '9'],

            ['-1', -2, '-0.01'],
            ['-1', -1, '-0.1'],
            ['-1', 0, '-1'],
            ['-1', 1, '-10'],
            ['-1', 2, '-100'],

            ['-0.1', -2, '-0.001'],
            ['-0.1', -1, '-0.01'],
            ['-0.1', 0, '-0.1'],
            ['-0.1', 1, '-1'],
            ['-0.1', 2, '-10'],

            ['-0.01', -2, '-0.0001'],
            ['-0.01', -1, '-0.001'],
            ['-0.01', 0, '-0.01'],
            ['-0.01', 1, '-0.1'],
            ['-0.01', 2, '-1'],

            ['-12.3', -2, '-0.123'],
            ['-12.3', -1, '-1.23'],
            ['-12.3', 0, '-12.3'],
            ['-12.3', 1, '-123'],
            ['-12.3', 2, '-1230'],
        ];
    }

    /**
     * @dataProvider providerStripTrailingZeros
     *
     * @param string $number   The number to trim.
     * @param string $expected The expected result.
     */
    public function testStripTrailingZeros($number, $expected)
    {
        $this->assertBigDecimalEquals($expected, BigDecimal::of($number)->stripTrailingZeros());
    }

    /**
     * @return array
     */
    public function providerStripTrailingZeros()
    {
        return [
            ['0', '0'],
            ['0.0', '0'],
            ['0.00', '0'],
            ['0.000', '0'],
            ['0.1', '0.1'],
            ['0.01', '0.01'],
            ['0.001', '0.001'],
            ['0.100', '0.1'],
            ['0.0100', '0.01'],
            ['0.00100', '0.001'],
            ['1', '1'],
            ['1.0', '1'],
            ['1.00', '1'],
            ['1.10', '1.1'],
            ['1.123000', '1.123'],
            ['10', '10'],
            ['10.0', '10'],
            ['10.00', '10'],
            ['10.10', '10.1'],
            ['10.01', '10.01'],
            ['10.010', '10.01'],
            ['100', '100'],
            ['100.0', '100'],
            ['100.00', '100'],
            ['100.01', '100.01'],
            ['100.10', '100.1'],
            ['100.010', '100.01'],
            ['100.100', '100.1'],
        ];
    }

    /**
     * @dataProvider providerAbs
     *
     * @param string $number        The number as a string.
     * @param string $unscaledValue The expected unscaled value of the absolute result.
     * @param int    $scale         The expected scale of the absolute result.
     */
    public function testAbs($number, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->abs());
    }

    /**
     * @return array
     */
    public function providerAbs()
    {
        return [
            ['123', '123', 0],
            ['-123', '123', 0],
            ['123.456', '123456', 3],
            ['-123.456', '123456', 3]
        ];
    }

    /**
     * @dataProvider providerNegated
     *
     * @param string $number        The number to negate as a string.
     * @param string $unscaledValue The expected unscaled value of the result.
     * @param int    $scale         The expected scale of the result.
     */
    public function testNegated($number, $unscaledValue, $scale)
    {
        $this->assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->negated());
    }

    /**
     * @return array
     */
    public function providerNegated()
    {
        return [
            ['123', '-123', 0],
            ['-123', '123', 0],
            ['123.456', '-123456', 3],
            ['-123.456', '123456', 3]
        ];
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The expected comparison result.
     */
    public function testCompareTo($a, $b, $c)
    {
        $this->assertSame($c, BigDecimal::of($a)->compareTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    public function testIsEqualTo($a, $b, $c)
    {
        $this->assertSame($c == 0, BigDecimal::of($a)->isEqualTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    public function testIsLessThan($a, $b, $c)
    {
        $this->assertSame($c < 0, BigDecimal::of($a)->isLessThan($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    public function testIsLessThanOrEqualTo($a, $b, $c)
    {
        $this->assertSame($c <= 0, BigDecimal::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    public function testIsGreaterThan($a, $b, $c)
    {
        $this->assertSame($c > 0, BigDecimal::of($a)->isGreaterThan($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    public function testIsGreaterThanOrEqualTo($a, $b, $c)
    {
        $this->assertSame($c >= 0, BigDecimal::of($a)->isGreaterThanOrEqualTo($b));
    }

    /**
     * @return array
     */
    public function providerCompareTo()
    {
        return [
            ['123', '123',  0],
            ['123', '456', -1],
            ['456', '123',  1],
            ['456', '456',  0],

            ['-123', '-123',  0],
            ['-123',  '456', -1],
            [ '456', '-123',  1],
            [ '456',  '456',  0],

            [ '123',  '123',  0],
            [ '123', '-456',  1],
            ['-456',  '123', -1],
            ['-456',  '456', -1],

            ['-123', '-123',  0],
            ['-123', '-456',  1],
            ['-456', '-123', -1],
            ['-456', '-456',  0],

            ['123.000000000000000000000000000000000000000000000', '123',  0],
            ['123.000000000000000000000000000000000000000000001', '123',  1],
            ['122.999999999999999999999999999999999999999999999', '123', -1],

            ['123.0', '123.000000000000000000000000000000000000000000000',  0],
            ['123.0', '123.000000000000000000000000000000000000000000001', -1],
            ['123.0', '122.999999999999999999999999999999999999999999999',  1],

            ['-0.000000000000000000000000000000000000000000000000001', '0', -1],
            [ '0.000000000000000000000000000000000000000000000000001', '0',  1],
            [ '0.000000000000000000000000000000000000000000000000000', '0',  0],

            ['0', '-0.000000000000000000000000000000000000000000000000001',  1],
            ['0',  '0.000000000000000000000000000000000000000000000000001', -1],
            ['0',  '0.000000000000000000000000000000000000000000000000000',  0],

            ['123.9999999999999999999999999999999999999', 124, -1],
            ['124.0000000000000000000000000000000000000', '124', 0],
            ['124.0000000000000000000000000000000000001', 124.0, 1],

            ['123.9999999999999999999999999999999999999', '1508517100733469660019804/12165460489786045645321', -1],
            ['124.0000000000000000000000000000000000000', '1508517100733469660019804/12165460489786045645321', 0],
            ['124.0000000000000000000000000000000000001', '1508517100733469660019804/12165460489786045645321', 1],
        ];
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testSign($number, $sign)
    {
        $this->assertSame($sign, BigDecimal::of($number)->sign());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsZero($number, $sign)
    {
        $this->assertSame($sign === 0, BigDecimal::of($number)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsNegative($number, $sign)
    {
        $this->assertSame($sign < 0, BigDecimal::of($number)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsNegativeOrZero($number, $sign)
    {
        $this->assertSame($sign <= 0, BigDecimal::of($number)->isNegativeOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsPositive($number, $sign)
    {
        $this->assertSame($sign > 0, BigDecimal::of($number)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsPositiveOrZero($number, $sign)
    {
        $this->assertSame($sign >= 0, BigDecimal::of($number)->isPositiveOrZero());
    }

    /**
     * @return array
     */
    public function providerSign()
    {
        return [
            [ 0,  0],
            [-0,  0],
            [ 1,  1],
            [-1, -1],

            [PHP_INT_MAX, 1],
            [~PHP_INT_MAX, -1],

            [ 1.0,  1],
            [-1.0, -1],
            [ 0.1,  1],
            [-0.1, -1],
            [ 0.0,  0],
            [-0.0,  0],

            [ '1.00',  1],
            ['-1.00', -1],
            [ '0.10',  1],
            ['-0.10', -1],
            [ '0.01',  1],
            ['-0.01', -1],
            [ '0.00',  0],
            ['-0.00',  0],

            [ '0.000000000000000000000000000000000000000000000000000000000000000000000000000001',  1],
            [ '0.000000000000000000000000000000000000000000000000000000000000000000000000000000',  0],
            ['-0.000000000000000000000000000000000000000000000000000000000000000000000000000001', -1]
        ];
    }

    /**
     * @dataProvider providerIntegral
     *
     * @param string $number   The number to test.
     * @param string $expected The expected integral value.
     */
    public function testIntegral($number, $expected)
    {
        $this->assertSame($expected, BigDecimal::of($number)->integral());
    }

    /**
     * @return array
     */
    public function providerIntegral()
    {
        return [
            ['1.23', '1'],
            ['-1.23', '-1'],
            ['0.123', '0'],
            ['0.001', '0'],
            ['123.0', '123'],
            ['12', '12'],
            ['1234.5678', '1234']
        ];
    }

    /**
     * @dataProvider providerFraction
     *
     * @param string $number   The number to test.
     * @param string $expected The expected fractional value.
     */
    public function testFraction($number, $expected)
    {
        $this->assertSame($expected, BigDecimal::of($number)->fraction());
    }

    /**
     * @return array
     */
    public function providerFraction()
    {
        return [
            ['1.23', '23'],
            ['-1.23', '23'],
            ['1', ''],
            ['-1', ''],
            ['0', ''],
            ['0.001', '001']
        ];
    }

    /**
     * @dataProvider providerToBigInteger
     *
     * @param string $decimal  The number to convert.=
     * @param string $expected The expected value.
     */
    public function testToBigInteger($decimal, $expected)
    {
        $this->assertBigIntegerEquals($expected, BigDecimal::of($decimal)->toBigInteger());
    }

    /**
     * @return array
     */
    public function providerToBigInteger()
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['0.0', '0'],
            ['1.0', '1'],
            ['-45646540654984984654165151654557478978940.0000000000000', '-45646540654984984654165151654557478978940'],
        ];
    }

    /**
     * @dataProvider providerToBigIntegerThrowsExceptionWhenRoundingNecessary
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     *
     * @param string $decimal A decimal number with a non-zero fractional part.
     */
    public function testToBigIntegerThrowsExceptionWhenRoundingNecessary($decimal)
    {
        BigDecimal::of($decimal)->toBigInteger();
    }

    /**
     * @return array
     */
    public function providerToBigIntegerThrowsExceptionWhenRoundingNecessary()
    {
        return [
            ['0.1'],
            ['-0.1'],
            ['0.01'],
            ['-0.01'],
            [ '1.002'],
            [ '0.001'],
            ['-1.002'],
            ['-0.001'],
            ['-45646540654984984654165151654557478978940.0000000000001'],
        ];
    }

    /**
     * @dataProvider providerToBigRational
     *
     * @param string $decimal  The decimal number to test.
     * @param string $rational The expected rational number.
     */
    public function testToBigRational($decimal, $rational)
    {
        $this->assertBigRationalEquals($rational, BigDecimal::of($decimal)->toBigRational());
    }

    /**
     * @return array
     */
    public function providerToBigRational()
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['-1', '-1'],

            ['0.0', '0/10'],
            ['1.0', '10/10'],
            ['-1.0', '-10/10'],

            ['0.00', '0/100'],
            ['1.00', '100/100'],
            ['-1.00', '-100/100'],

            ['0.9', '9/10'],
            ['0.90', '90/100'],
            ['0.900', '900/1000'],

            ['0.10', '10/100'],
            ['0.11', '11/100'],
            ['0.99', '99/100'],
            ['0.990', '990/1000'],
            ['0.9900', '9900/10000'],

            ['1.01', '101/100'],
            ['-1.001', '-1001/1000'],
            ['-1.010', '-1010/1000'],

            ['77867087546465423456465427464560454054654.4211684848', '778670875464654234564654274645604540546544211684848/10000000000']
        ];
    }

    /**
     * @dataProvider providerToInteger
     *
     * @param int $number The decimal number to test.
     */
    public function testToInteger($number)
    {
        $this->assertSame($number, BigDecimal::of($number)->toInteger());
        $this->assertSame($number, BigDecimal::of($number . '.0')->toInteger());
    }

    /**
     * @return array
     */
    public function providerToInteger()
    {
        return [
            [~PHP_INT_MAX],
            [-123456789],
            [-1],
            [0],
            [1],
            [123456789],
            [PHP_INT_MAX]
        ];
    }

    /**
     * @dataProvider providerToIntegerThrowsException
     * @expectedException \Brick\Math\Exception\ArithmeticException
     *
     * @param string $number A valid decimal number that cannot safely be converted to a native integer.
     */
    public function testToIntegerThrowsException($number)
    {
        BigDecimal::of($number)->toInteger();
    }

    /**
     * @return array
     */
    public function providerToIntegerThrowsException()
    {
        return [
            ['-999999999999999999999999999999'],
            ['9999999999999999999999999999999'],
            ['1.2'],
            ['-1.2'],
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
        $this->assertSame($float, BigDecimal::of($value)->toFloat());
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
            ['9.999999999999999999999999999999999999999999999999999999999999', 9.999999999999999999999999999999],
            ['-9.999999999999999999999999999999999999999999999999999999999999', -9.999999999999999999999999999999],
            ['9.9e3000', INF],
            ['-9.9e3000', -INF],
        ];
    }

    /**
     * @dataProvider providerToString
     *
     * @param string $unscaledValue The unscaled value.
     * @param int    $scale         The scale.
     * @param string $expected      The expected string representation.
     */
    public function testToString($unscaledValue, $scale, $expected)
    {
        $this->assertSame($expected, (string) BigDecimal::ofUnscaledValue($unscaledValue, $scale));
    }

    /**
     * @return array
     */
    public function providerToString()
    {
        return [
            ['0',   0, '0'],
            ['0',   1, '0.0'],
            ['1',   1, '0.1'],
            ['0',   2, '0.00'],
            ['1',   2, '0.01'],
            ['10',  2, '0.10'],
            ['11',  2, '0.11'],
            ['11',  3, '0.011'],
            ['1',   0, '1'],
            ['10',  1, '1.0'],
            ['11',  1, '1.1'],
            ['100', 2, '1.00'],
            ['101', 2, '1.01'],
            ['110', 2, '1.10'],
            ['111', 2, '1.11'],
            ['111', 3, '0.111'],
            ['111', 4, '0.0111'],

            ['-1',   1, '-0.1'],
            ['-1',   2, '-0.01'],
            ['-10',  2, '-0.10'],
            ['-11',  2, '-0.11'],
            ['-12',  3, '-0.012'],
            ['-12',  4, '-0.0012'],
            ['-1',   0, '-1'],
            ['-10',  1, '-1.0'],
            ['-12',  1, '-1.2'],
            ['-100', 2, '-1.00'],
            ['-101', 2, '-1.01'],
            ['-120', 2, '-1.20'],
            ['-123', 2, '-1.23'],
            ['-123', 3, '-0.123'],
            ['-123', 4, '-0.0123'],
        ];
    }

    public function testSerialize()
    {
        $value = '-1234567890987654321012345678909876543210123456789';
        $scale = 37;

        $number = BigDecimal::ofUnscaledValue($value, $scale);

        $this->assertBigDecimalInternalValues($value, $scale, unserialize(serialize($number)));
    }

    /**
     * @expectedException \LogicException
     */
    public function testDirectCallToUnserialize()
    {
        BigDecimal::zero()->unserialize('123:0');
    }
}
