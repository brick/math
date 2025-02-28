<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class BigDecimal.
 */
class BigDecimalTest extends AbstractTestCase
{
    /**
     * @param int|float|string $value         The value to convert to a BigDecimal.
     * @param string           $unscaledValue The expected unscaled value.
     * @param int              $scale         The expected scale.
     */
    #[DataProvider('providerOf')]
    public function testOf(int|float|string $value, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($value));
    }

    public static function providerOf() : array
    {
        return [
            [0, '0', 0],
            [1, '1', 0],
            [-1, '-1', 0],
            [123456789, '123456789', 0],
            [-123456789, '-123456789', 0],
            [PHP_INT_MAX, (string) PHP_INT_MAX, 0],
            [PHP_INT_MIN, (string) PHP_INT_MIN, 0],

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

            ['.0', '0', 1],
            ['.00', '0', 2],
            ['.123', '123', 3],
            ['+.2', '2', 1],
            ['-.33', '-33', 2],
            ['1.e2', '100', 0],
            ['.1e-1', '1', 2],
            ['.1e0', '1', 1],
            ['.1e1', '1', 0],
            ['.1e2', '10', 0],
            ['1.e-2', '1', 2],
            ['.1e-2', '1', 3],
            ['.12e1', '12', 1],
            ['.012e1', '12', 2],
            ['-.15e3', '-150', 0],

            ['1.', '1', 0],
            ['+12.', '12', 0],
            ['-123.', '-123', 0],

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

    #[DataProvider('providerOfFloatInDifferentLocales')]
    public function testOfFloatInDifferentLocales(string $locale) : void
    {
        $originalLocale = setlocale(LC_NUMERIC, '0');
        $setLocale = setlocale(LC_NUMERIC, $locale);

        if ($setLocale !== $locale) {
            setlocale(LC_NUMERIC, $originalLocale);
            self::markTestSkipped('Locale ' . $locale . ' is not supported on this system.');
        }

        // Test a large enough number (thousands separator) with decimal digits (decimal separator)
        self::assertSame('2500.5', (string) BigDecimal::of(5001/2));

        // Ensure that the locale has been reset to its original value by BigNumber::of()
        self::assertSame($locale, setlocale(LC_NUMERIC, '0'));

        setlocale(LC_NUMERIC, $originalLocale);
    }

    public static function providerOfFloatInDifferentLocales() : array
    {
        return [
            ['C'],
            ['en_US.UTF-8'],
            ['de_DE.UTF-8'],
            ['es_ES'],
            ['fr_FR'],
            ['fr_FR.iso88591'],
            ['fr_FR.iso885915@euro'],
            ['fr_FR@euro'],
            ['fr_FR.utf8'],
            ['ps_AF'],
        ];
    }

    #[DataProvider('providerOfInvalidValueThrowsException')]
    public function testOfInvalidValueThrowsException(int|float|string $value) : void
    {
        $this->expectException(NumberFormatException::class);
        BigDecimal::of($value);
    }

    public static function providerOfInvalidValueThrowsException() : array
    {
        return [
            [''],
            ['a'],
            [' 1'],
            ['1 '],
            ['..1'],
            ['1..'],
            ['.1.'],
            ['+'],
            ['-'],
            ['.'],
            ['1e'],
            ['.e'],
            ['.e1'],
            ['1e+'],
            ['1e-'],
            ['+e1'],
            ['-e2'],
            ['.e3'],
            ['+a'],
            ['-a'],
            ['1e1000000000000000000000000000000'],
            ['1e-1000000000000000000000000000000'],
            [INF],
            [-INF],
            [NAN],
        ];
    }

    public function testOfBigDecimalReturnsThis() : void
    {
        $decimal = BigDecimal::of(123);

        self::assertSame($decimal, BigDecimal::of($decimal));
    }

    /**
     * @param int|string $unscaledValue         The unscaled value of the BigDecimal to create.
     * @param int        $scale                 The scale of the BigDecimal to create.
     * @param string     $expectedUnscaledValue The expected result unscaled value.
     */
    #[DataProvider('providerOfUnscaledValue')]
    public function testOfUnscaledValue(int|string $unscaledValue, int $scale, string $expectedUnscaledValue) : void
    {
        $number = BigDecimal::ofUnscaledValue($unscaledValue, $scale);
        self::assertBigDecimalInternalValues($expectedUnscaledValue, $scale, $number);
    }

    public static function providerOfUnscaledValue() : array
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

    public function testOfUnscaledValueWithDefaultScale() : void
    {
        $number = BigDecimal::ofUnscaledValue('123456789');
        self::assertBigDecimalInternalValues('123456789', 0, $number);
    }

    public function testOfUnscaledValueWithNegativeScaleThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigDecimal::ofUnscaledValue('0', -1);
    }

    public function testZero() : void
    {
        self::assertBigDecimalInternalValues('0', 0, BigDecimal::zero());
        self::assertSame(BigDecimal::zero(), BigDecimal::zero());
    }

    public function testOne() : void
    {
        self::assertBigDecimalInternalValues('1', 0, BigDecimal::one());
        self::assertSame(BigDecimal::one(), BigDecimal::one());
    }

    public function testTen() : void
    {
        self::assertBigDecimalInternalValues('10', 0, BigDecimal::ten());
        self::assertSame(BigDecimal::ten(), BigDecimal::ten());
    }

    /**
     * @param array  $values The values to compare.
     * @param string $min    The expected minimum value.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $values, string $min) : void
    {
        self::assertBigDecimalEquals($min, BigDecimal::min(... $values));
    }

    public static function providerMin() : array
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

    public function testMinOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigDecimal::min();
    }

    public function testMinOfNonDecimalValuesThrowsException() : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::min(1, '1/3');
    }

    /**
     * @param array  $values The values to compare.
     * @param string $max    The expected maximum value.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $values, string $max) : void
    {
        self::assertBigDecimalEquals($max, BigDecimal::max(... $values));
    }

    public static function providerMax() : array
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

    public function testMaxOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigDecimal::max();
    }

    public function testMaxOfNonDecimalValuesThrowsException() : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::min(1, '3/7');
    }

    /**
     * @param array  $values The values to add.
     * @param string $sum    The expected sum.
     */
    #[DataProvider('providerSum')]
    public function testSum(array $values, string $sum) : void
    {
        self::assertBigDecimalEquals($sum, BigDecimal::sum(... $values));
    }

    public static function providerSum() : array
    {
        return [
            [[0, 0.9, -1.00], '-0.1'],
            [[0, 0.01, -1, -1.2], '-2.19'],
            [[0, 0.01, -1, -1.2, '2e-1'], '-1.99'],
            [['1e-30', '123456789123456789123456789', 2e25], '143456789123456789123456789.000000000000000000000000000001'],
            [['1e-30', '123456789123456789123456789', 2e26], '323456789123456789123456789.000000000000000000000000000001'],
            [[0, '10', '5989', '-1'], '5998'],
            [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1'], '11987.000000000000000000000000000000001'],
            [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1', '5990'], '17977.000000000000000000000000000000001'],
            [['-0.0000000000000000000000000000001', 0], '-0.0000000000000000000000000000001'],
            [['0.00000000000000000000000000000001', '0'], '0.00000000000000000000000000000001'],
            [['-1', '1', '2', '3', '-99.1'], '-94.1'],
            [['-1', '1', '2', '3', '-99.1', '31/10'], '-91.0'],
            [['999999999999999999999999999.99999999999', '1000000000000000000000000000'], '1999999999999999999999999999.99999999999'],
            [['-999999999999999999999999999.99999999999', 47, '-1000000000000000000000000000'], '-1999999999999999999999999952.99999999999'],
            [['9.9e50', '1e50', '-3/2'], '1089999999999999999999999999999999999999999999999998.5'],
            [['9.9e50', '-1e-51'], '989999999999999999999999999999999999999999999999999.999999999999999999999999999999999999999999999999999'],
        ];
    }

    public function testSumOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigDecimal::sum();
    }

    public function testSumOfNonDecimalValuesThrowsException() : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::min(1, '3/7');
    }

    /**
     * @param string $a             The base number.
     * @param string $b             The number to add.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    #[DataProvider('providerPlus')]
    public function testPlus(string $a, string $b, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->plus($b));
    }

    public static function providerPlus() : array
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

            ['0',    '999',    '999',   0],
            ['0',    '999.0',  '9990',  1],
            ['0',    '999.00', '99900', 2],
            ['0.0',  '999',    '9990',  1],
            ['0.0',  '999.0',  '9990',  1],
            ['0.0',  '999.00', '99900', 2],
            ['0.00', '999',    '99900', 2],
            ['0.00', '999.0',  '99900', 2],
            ['0.00', '999.00', '99900', 2],

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
     * @param string $a             The base number.
     * @param string $b             The number to subtract.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    #[DataProvider('providerMinus')]
    public function testMinus(string $a, string $b, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->minus($b));
    }

    public static function providerMinus() : array
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
            ['0',      '999',    '-999',   0],
            ['0',      '999.0',  '-9990',  1],

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
            ['0', '1234568798347983.2334899238921', '-12345687983479832334899238921', 13],
            ['-0.00223287647368738736428467863784', '0.000', '-223287647368738736428467863784', 32],
        ];
    }

    /**
     * @param string $a             The base number.
     * @param string $b             The number to multiply.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(string $a, string $b, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->multipliedBy($b));
    }

    public static function providerMultipliedBy() : array
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

            ['123.0', '0.1', '1230', 2],
            ['123.0', '0.01', '1230', 3],
            ['123.1', '0.01', '1231', 3],
            ['123.1', '0.001', '1231', 4],

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

            ['1',    '999',    '999',     0],
            ['1',    '999.0',  '9990',    1],
            ['1',    '999.00', '99900',   2],
            ['1.0',  '999',    '9990',    1],
            ['1.0',  '999.0',  '99900',   2],
            ['1.0',  '999.00', '999000',  3],
            ['1.00', '999',    '99900',   2],
            ['1.00', '999.0',  '999000',  3],
            ['1.00', '999.00', '9990000', 4],

            ['123',    '1',    '123',     0],
            ['123',    '1.0',  '1230',    1],
            ['123',    '1.00', '12300',   2],
            ['123.0',  '1',    '1230',    1],
            ['123.0',  '1.0',  '12300',   2],
            ['123.0',  '1.00', '123000',  3],
            ['123.00', '1',    '12300',   2],
            ['123.00', '1.0',  '123000',  3],
            ['123.00', '1.00', '1230000', 4],

            ['0',    '999',    '0', 0],
            ['0',    '999.0',  '0', 1],
            ['0',    '999.00', '0', 2],
            ['0.0',  '999',    '0', 1],
            ['0.0',  '999.0',  '0', 2],
            ['0.0',  '999.00', '0', 3],
            ['0.00', '999',    '0', 2],
            ['0.00', '999.0',  '0', 3],
            ['0.00', '999.00', '0', 4],

            ['123',    '0',    '0', 0],
            ['123',    '0.0',  '0', 1],
            ['123',    '0.00', '0', 2],
            ['123.0',  '0',    '0', 1],
            ['123.0',  '0.0',  '0', 2],
            ['123.0',  '0.00', '0', 3],
            ['123.00', '0',    '0', 2],
            ['123.00', '0.0',  '0', 3],
            ['123.00', '0.00', '0', 4],

            ['589252.156111130', '999.2563989942545241223454', '5888139876152080735720775399923986443020', 31],
            ['-589252.15611130', '999.256398994254524122354', '-58881398761537794715991163083004200020', 29],
            ['589252.1561113', '-99.256398994254524122354', '-584870471152079471599116308300420002', 28],
            ['-58952.156111', '-9.256398994254524122357', '545684678534996098129205129273627', 27],

            ['0.1235437849158495728979344999999999999', '1', '1235437849158495728979344999999999999', 37],
            ['-1.324985980890283098409328999999999999', '1', '-1324985980890283098409328999999999999', 36],
        ];
    }

    /**
     * @param string       $a             The base number.
     * @param string       $b             The number to divide.
     * @param int|null     $scale         The desired scale of the result.
     * @param RoundingMode $roundingMode  The rounding mode.
     * @param string       $unscaledValue The expected unscaled value of the result.
     * @param int          $expectedScale The expected scale of the result.
     */
    #[DataProvider('providerDividedBy')]
    public function testDividedBy(string $a, string $b, ?int $scale, RoundingMode $roundingMode, string $unscaledValue, int $expectedScale) : void
    {
        $decimal = BigDecimal::of($a)->dividedBy($b, $scale, $roundingMode);
        self::assertBigDecimalInternalValues($unscaledValue, $expectedScale, $decimal);
    }

    public static function providerDividedBy() : array
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

    #[DataProvider('providerDividedByByZeroThrowsException')]
    public function testDividedByByZeroThrowsException(int|float|string $zero) : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1)->dividedBy($zero, 0);
    }

    public static function providerDividedByByZeroThrowsException() : array
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
     * @param int|float|string $number   The number to divide.
     * @param int|float|string $divisor  The divisor.
     * @param string           $expected The expected result, or a class name if an exception is expected.
     */
    #[DataProvider('providerExactlyDividedBy')]
    public function testExactlyDividedBy(int|float|string $number, int|float|string $divisor, string $expected) : void
    {
        $number = BigDecimal::of($number);

        if (self::isException($expected)) {
            $this->expectException($expected);
        }

        $actual = $number->exactlyDividedBy($divisor);

        if (! self::isException($expected)) {
            self::assertBigDecimalEquals($expected, $actual);
        }
    }

    public static function providerExactlyDividedBy() : array
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

    public function testExactlyDividedByZero() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1)->exactlyDividedBy(0);
    }

    /**
     * @param string $a     The base number.
     * @param string $b     The number to divide by.
     * @param int    $scale The desired scale.
     */
    #[DataProvider('providerDividedByWithRoundingNecessaryThrowsException')]
    public function testDividedByWithRoundingNecessaryThrowsException(string $a, string $b, int $scale) : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::of($a)->dividedBy($b, $scale);
    }

    public static function providerDividedByWithRoundingNecessaryThrowsException() : array
    {
        return [
            ['1.234', '123.456', 3],
            ['7', '2', 0],
            ['7', '3', 100],
        ];
    }

    public function testDividedByWithNegativeScaleThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigDecimal::of(1)->dividedBy(2, -1);
    }

    /**
     * @param RoundingMode $roundingMode The rounding mode.
     * @param string       $number       The number to round.
     * @param string|null  $two          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null  $one          The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null  $zero         The expected rounding to a scale of zero, or null if an exception is expected.
     */
    #[DataProvider('providerRoundingMode')]
    public function testRoundingMode(RoundingMode $roundingMode, string $number, ?string $two, ?string $one, ?string $zero) : void
    {
        $number = BigDecimal::of($number);

        $this->doTestRoundingMode($roundingMode, $number, '1', $two, $one, $zero);
        $this->doTestRoundingMode($roundingMode, $number->negated(), '-1', $two, $one, $zero);
    }

    /**
     * @param RoundingMode $roundingMode The rounding mode.
     * @param BigDecimal   $number       The number to round.
     * @param string       $divisor      The divisor.
     * @param string|null  $two          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null  $one          The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null  $zero         The expected rounding to a scale of zero, or null if an exception is expected.
     */
    private function doTestRoundingMode(RoundingMode $roundingMode, BigDecimal $number, string $divisor, ?string $two, ?string $one, ?string $zero) : void
    {
        foreach ([$zero, $one, $two] as $scale => $expected) {
            if ($expected === null) {
                $this->expectException(RoundingNecessaryException::class);
            }

            $actual = $number->dividedBy($divisor, $scale, $roundingMode);

            if ($expected !== null) {
                self::assertBigDecimalInternalValues($expected, $scale, $actual);
            }
        }
    }

    public static function providerRoundingMode() : array
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
     * @param string $dividend  The dividend.
     * @param string $divisor   The divisor.
     * @param string $quotient  The expected quotient.
     * @param string $remainder The expected remainder.
     */
    #[DataProvider('providerQuotientAndRemainder')]
    public function testQuotientAndRemainder(string $dividend, string $divisor, string $quotient, string $remainder) : void
    {
        $dividend = BigDecimal::of($dividend);

        self::assertBigDecimalEquals($quotient, $dividend->quotient($divisor));
        self::assertBigDecimalEquals($remainder, $dividend->remainder($divisor));

        [$q, $r] = $dividend->quotientAndRemainder($divisor);

        self::assertBigDecimalEquals($quotient, $q);
        self::assertBigDecimalEquals($remainder, $r);
    }

    public static function providerQuotientAndRemainder() : array
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

    public function testQuotientOfZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1.2)->quotient(0);
    }

    public function testRemainderOfZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1.2)->remainder(0);
    }

    public function testQuotientAndRemainderOfZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1.2)->quotientAndRemainder(0);
    }

    #[DataProvider('providerSqrt')]
    public function testSqrt(string $number, int $scale, string $sqrt) : void
    {
        $number = BigDecimal::of($number);

        self::assertBigDecimalEquals($sqrt, $number->sqrt($scale));
    }

    public static function providerSqrt() : array
    {
        return [
            ['0', 0, '0'],
            ['0', 1, '0.0'],
            ['0', 2, '0.00'],
            ['0.9', 0, '0'],
            ['0.9', 1, '0.9'],
            ['0.9', 2, '0.94'],
            ['0.9', 20, '0.94868329805051379959'],

            ['1', 0, '1'],
            ['1', 1, '1.0'],
            ['1', 2, '1.00'],
            ['1.01', 0, '1'],
            ['1.01', 1, '1.0'],
            ['1.01', 2, '1.00'],
            ['1.01', 50, '1.00498756211208902702192649127595761869450234700263'],

            ['2', 0, '1'],
            ['2', 1, '1.4'],
            ['2', 2, '1.41'],
            ['2', 3, '1.414'],
            ['2.0', 10, '1.4142135623'],
            ['2.00', 100, '1.4142135623730950488016887242096980785696718753769480731766797379907324784621070388503875343276415727'],
            ['2.01', 100, '1.4177446878757825202955618542708577926112284524295925478183838620667251915680282359142910339946198902'],

            ['3', 0, '1'],
            ['3', 1, '1.7'],
            ['3', 2, '1.73'],
            ['3.0', 3, '1.732'],
            ['3.00', 100, '1.7320508075688772935274463415058723669428052538103806280558069794519330169088000370811461867572485756'],
            ['3.01', 100, '1.7349351572897472412324994276999816954904345949805056180301062018688462654791174593725963060697252989'],

            ['4', 0, '2'],
            ['4.0', 1, '2.0'],
            ['4.00', 2, '2.00'],
            ['4.000', 50, '2.00000000000000000000000000000000000000000000000000'],
            ['4.001', 50, '2.00024998437695281987761450010498155779765165614814'],

            ['8', 0, '2'],
            ['8', 1, '2.8'],
            ['8', 2, '2.82'],
            ['8', 3, '2.828'],
            ['8', 100, '2.8284271247461900976033774484193961571393437507538961463533594759814649569242140777007750686552831454'],

            ['9', 0, '3'],
            ['9', 1, '3.0'],
            ['9', 2, '3.00'],
            ['9.0', 3, '3.000'],
            ['9.00', 50, '3.00000000000000000000000000000000000000000000000000'],
            ['9.000000000001', 100, '3.0000000000001666666666666620370370370372942386831275541552354823973654295585021450670206100119695201'],

            ['15', 0, '3'],
            ['15', 1, '3.8'],
            ['15', 2, '3.87'],
            ['15', 3, '3.872'],
            ['15', 100, '3.8729833462074168851792653997823996108329217052915908265875737661134830919369790335192873768586735179'],

            ['16', 0, '4'],
            ['16', 1, '4.0'],
            ['16.0', 2, '4.00'],
            ['16.0', 50, '4.00000000000000000000000000000000000000000000000000'],
            ['16.9', 100, '4.1109609582188931315985616077625340938354216811227818749147563086303727702310096877475225408930903837'],

            ['24.000000', 0, '4'],
            ['24.000000', 1, '4.8'],
            ['24.000000', 100, '4.8989794855663561963945681494117827839318949613133402568653851345019207549146300530797188662092804696'],

            ['25.0', 0, '5'],
            ['25.0', 1, '5.0'],
            ['25.0', 2, '5.00'],
            ['25.0', 50, '5.00000000000000000000000000000000000000000000000000'],

            ['35.0', 0, '5'],
            ['35.0', 1, '5.9'],
            ['35.0', 2, '5.91'],
            ['35.0', 3, '5.916'],
            ['35.0', 4, '5.9160'],
            ['35.0', 5, '5.91607'],
            ['35.0', 100, '5.9160797830996160425673282915616170484155012307943403228797196691428224591056530367657525271831091780'],
            ['35.000000000000001', 100, '5.9160797830996161270827537644132741956957234470198942745396537100863774127246283998019188486148209315'],
            ['35.999999999999999999999999', 100, '5.9999999999999999999999999166666666666666666666666660879629629629629629629629549254115226337448559670'],

            ['36.00', 0, '6'],
            ['36.00', 1, '6.0'],
            ['36.00', 2, '6.00'],
            ['36.00', 3, '6.000'],
            ['36.00', 50, '6.00000000000000000000000000000000000000000000000000'],

            ['48.00', 0, '6'],
            ['48.00', 2, '6.92'],
            ['48.00', 10, '6.9282032302'],
            ['48.00', 100, '6.9282032302755091741097853660234894677712210152415225122232279178077320676352001483245847470289943027'],
            ['48.99', 100, '6.9992856778388464346356995151906110076016504604210370025102717611026824990288822856842902895079113686'],

            ['49.000', 0, '7'],
            ['49.000', 1, '7.0'],
            ['49.000', 2, '7.00'],
            ['49.000', 50, '7.00000000000000000000000000000000000000000000000000'],

            ['63.000', 0, '7'],
            ['63.000', 1, '7.9'],
            ['63.000', 50, '7.93725393319377177150484726091778127713077754924735'],
            ['63.000', 100, '7.9372539331937717715048472609177812771307775492473505411050033776032064696908508832811786594236308318'],
            ['63.999', 100, '7.9999374997558574676327405322784897796491608172719005229581557200716046333750586163480165729931946120'],

            ['64.000', 0, '8'],
            ['64.000', 1, '8.0'],
            ['64.000', 2, '8.00'],
            ['64.000', 3, '8.000'],
            ['64.000', 5, '8.00000'],
            ['64.000', 10, '8.0000000000'],
            ['64.001', 100, '8.0000624997558612823300065647321162325407871131077227756517693705917932138407362275702583154308502098'],

            ['80.0000', 0, '8'],
            ['80.0000', 1, '8.9'],
            ['80.0000', 2, '8.94'],
            ['80.0000', 3, '8.944'],
            ['80.0000', 100, '8.9442719099991587856366946749251049417624734384461028970835889816420837025512195976576576335151290998'],
            ['80.9999', 100, '8.9999944444427297657453970731875168384468609868424736666730189165386046474591090720552569708711327582'],

            ['81.0000', 0, '9'],
            ['81.0000', 1, '9.0'],
            ['81.0000', 2, '9.00'],
            ['81.0000', 3, '9.000'],
            ['81.0000', 100, '9.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'],
            ['81.0001', 100, '9.0000055555538408789733941603538253684017661110877201067416238610513773808272313229667905649560045446'],

            ['99.0000', 0, '9'],
            ['99.0000', 1, '9.9'],
            ['99.0000', 2, '9.94'],
            ['99.0000', 3, '9.949'],
            ['99.0000', 100, '9.9498743710661995473447982100120600517812656367680607911760464383494539278271315401265301973848719527'],
            ['99.9999', 100, '9.9999949999987499993749996093747265622949217138670565794807433154677543830170797681772155388691566433'],

            ['100.00000', 0, '10'],
            ['100.00000', 1, '10.0'],
            ['100.00000', 2, '10.00'],
            ['100.00000', 3, '10.000'],
            ['100.00000', 100, '10.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'],
            ['100.00001', 100, '10.0000004999999875000006249999609375027343747949218911132799407960075378325233467481612458396019939178'],

            ['536137214136734800142146901786039940282473271927911507640625', 100, '732213912826528310663262741625.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'],
            ['536137214136734800142146901787504368108126328549238033123875', 100, '732213912826528310663262741625.9999999999999999999999999999993171394434860226777473099041602996062918768042176806905944729779944325'],
            ['536137214136734800142146901787504368108126328549238033123876', 100, '732213912826528310663262741626.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'],
            ['5651495859544574019979802175954184725583245698990648064256.0000000001', 100, '75176431543034642899535752016.0000000000000000000000000000000000000006651020668808623891656648072197795077909627885735661691784990'],
            ['5651495859544574019979802176104537588669314984789719568288.9999999999', 100, '75176431543034642899535752016.9999999999999999999999999999999999999993348979331191376108343351927890677073964211143577833889761307'],
            ['5651495859544574019979802176104537588669314984789719568289.00000000001', 100, '75176431543034642899535752017.0000000000000000000000000000000000000000665102066880862389165664807210932292603578885642216611023869'],

            ['17', 60, '4.123105625617660549821409855974077025147199225373620434398633'],
            ['17', 61, '4.1231056256176605498214098559740770251471992253736204343986335'],
            ['17', 62, '4.12310562561766054982140985597407702514719922537362043439863357'],
            ['17', 63, '4.123105625617660549821409855974077025147199225373620434398633573'],
            ['17', 64, '4.1231056256176605498214098559740770251471992253736204343986335730'],
            ['17', 65, '4.12310562561766054982140985597407702514719922537362043439863357309'],
            ['17', 66, '4.123105625617660549821409855974077025147199225373620434398633573094'],
            ['17', 67, '4.1231056256176605498214098559740770251471992253736204343986335730949'],
            ['17', 68, '4.12310562561766054982140985597407702514719922537362043439863357309495'],
            ['17', 69, '4.123105625617660549821409855974077025147199225373620434398633573094954'],
            ['17', 70, '4.1231056256176605498214098559740770251471992253736204343986335730949543'],

            ['0.0019', 0, '0'],
            ['0.0019', 1, '0.0'],
            ['0.0019', 2, '0.04'],
            ['0.0019', 3, '0.043'],
            ['0.0019', 10, '0.0435889894'],
            ['0.0019', 70, '0.0435889894354067355223698198385961565913700392523244493689034413815955'],

            ['0.00000000015727468406479', 0, '0'],
            ['0.00000000015727468406479', 1, '0.0'],
            ['0.00000000015727468406479', 2, '0.00'],
            ['0.00000000015727468406479', 3, '0.000'],
            ['0.00000000015727468406479', 4, '0.0000'],
            ['0.00000000015727468406479', 5, '0.00001'],
            ['0.00000000015727468406479', 6, '0.000012'],
            ['0.00000000015727468406479', 7, '0.0000125'],
            ['0.00000000015727468406479', 8, '0.00001254'],
            ['0.00000000015727468406479', 9, '0.000012540'],
            ['0.00000000015727468406479', 10, '0.0000125409'],
            ['0.00000000015727468406479', 100, '0.0000125409203834802332262270521125445995500262491027973910117525063503841909945796984522050136239469'],

            ['0.04', 0, '0'],
            ['0.04', 1, '0.2'],
            ['0.04', 2, '0.20'],
            ['0.04', 10, '0.2000000000'],

            ['0.0004', 4, '0.0200'],
            ['0.00000000000000000000000000000004', 8, '0.00000000'],
            ['0.00000000000000000000000000000004', 16, '0.0000000000000002'],
            ['0.00000000000000000000000000000004', 32, '0.00000000000000020000000000000000'],
            ['0.000000000000000000000000000000004', 32, '0.00000000000000006324555320336758'],

            ['111111111111111111111.11111111111111', 90, '10540925533.894597773329645148109061726360556128277733889543457102096672435043305908711407747018689086']
        ];
    }

    public function testSqrtOfNegativeNumber() : void
    {
        $number = BigDecimal::of(-1);
        $this->expectException(NegativeNumberException::class);
        $number->sqrt(0);
    }

    public function testSqrtWithNegativeScale() : void
    {
        $number = BigDecimal::of(1);
        $this->expectException(\InvalidArgumentException::class);
        $number->sqrt(-1);
    }

    /**
     * @param string  $number        The base number.
     * @param int     $exponent      The exponent to apply.
     * @param string  $unscaledValue The expected unscaled value of the result.
     * @param int     $scale         The expected scale of the result.
     */
    #[DataProvider('providerPower')]
    public function testPower(string $number, int $exponent, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->power($exponent));
    }

    public static function providerPower() : array
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

    #[DataProvider('providerPowerWithInvalidExponentThrowsException')]
    public function testPowerWithInvalidExponentThrowsException(int $power) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigDecimal::of(1)->power($power);
    }

    public static function providerPowerWithInvalidExponentThrowsException() : array
    {
        return [
            [-1],
            [1000001]
        ];
    }

    /**
     * @param string       $number        The number to scale.
     * @param int          $toScale       The scale to apply.
     * @param RoundingMode $roundingMode  The rounding mode to apply.
     * @param string       $unscaledValue The expected unscaled value of the result.
     * @param int          $scale         The expected scale of the result.
     */
    #[DataProvider('providerToScale')]
    public function testToScale(string $number, int $toScale, RoundingMode $roundingMode, string $unscaledValue, int $scale) : void
    {
        $decimal = BigDecimal::of($number)->toScale($toScale, $roundingMode);
        self::assertBigDecimalInternalValues($unscaledValue, $scale, $decimal);
    }

    public static function providerToScale() : array
    {
        return [
            ['123.45', 0, RoundingMode::DOWN, '123', 0],
            ['123.45', 1, RoundingMode::UP, '1235', 1],
            ['123.45', 2, RoundingMode::UNNECESSARY, '12345', 2],
            ['123.45', 5, RoundingMode::UNNECESSARY, '12345000', 5]
        ];
    }

    /**
     * @param string $number   The decimal number as a string.
     * @param int    $places   The number of decimal places to move left.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerWithPointMovedLeft')]
    public function testWithPointMovedLeft(string $number, int $places, string $expected) : void
    {
        self::assertBigDecimalEquals($expected, BigDecimal::of($number)->withPointMovedLeft($places));
    }

    public static function providerWithPointMovedLeft() : array
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
     * @param string $number   The decimal number as a string.
     * @param int    $places   The number of decimal places to move right.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerWithPointMovedRight')]
    public function testWithPointMovedRight(string $number, int $places, string $expected) : void
    {
        self::assertBigDecimalEquals($expected, BigDecimal::of($number)->withPointMovedRight($places));
    }

    public static function providerWithPointMovedRight() : array
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
     * @param string $number   The number to trim.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerStripTrailingZeros')]
    public function testStripTrailingZeros(string $number, string $expected) : void
    {
        self::assertBigDecimalEquals($expected, BigDecimal::of($number)->stripTrailingZeros());
    }

    public static function providerStripTrailingZeros() : array
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
     * @param string $number        The number as a string.
     * @param string $unscaledValue The expected unscaled value of the absolute result.
     * @param int    $scale         The expected scale of the absolute result.
     */
    #[DataProvider('providerAbs')]
    public function testAbs(string $number, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->abs());
    }

    public static function providerAbs() : array
    {
        return [
            ['123', '123', 0],
            ['-123', '123', 0],
            ['123.456', '123456', 3],
            ['-123.456', '123456', 3]
        ];
    }

    /**
     * @param string $number        The number to negate as a string.
     * @param string $unscaledValue The expected unscaled value of the result.
     * @param int    $scale         The expected scale of the result.
     */
    #[DataProvider('providerNegated')]
    public function testNegated(string $number, string $unscaledValue, int $scale) : void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->negated());
    }

    public static function providerNegated() : array
    {
        return [
            ['123', '-123', 0],
            ['-123', '123', 0],
            ['123.456', '-123456', 3],
            ['-123.456', '123456', 3]
        ];
    }

    /**
     * @param string           $a The base number as a string.
     * @param int|float|string $b The number to compare to.
     * @param int              $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testCompareTo(string $a, int|float|string $b, int $c) : void
    {
        self::assertSame($c, BigDecimal::of($a)->compareTo($b));
    }

    /**
     * @param string           $a The base number as a string.
     * @param int|float|string $b The number to compare to.
     * @param int              $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsEqualTo(string $a, int|float|string $b, int $c) : void
    {
        self::assertSame($c === 0, BigDecimal::of($a)->isEqualTo($b));
    }

    /**
     * @param string           $a The base number as a string.
     * @param int|float|string $b The number to compare to.
     * @param int              $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThan(string $a, int|float|string $b, int $c) : void
    {
        self::assertSame($c < 0, BigDecimal::of($a)->isLessThan($b));
    }

    /**
     * @param string           $a The base number as a string.
     * @param int|float|string $b The number to compare to.
     * @param int              $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThanOrEqualTo(string $a, int|float|string $b, int $c) : void
    {
        self::assertSame($c <= 0, BigDecimal::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @param string           $a The base number as a string.
     * @param int|float|string $b The number to compare to.
     * @param int              $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThan(string $a, int|float|string $b, int $c) : void
    {
        self::assertSame($c > 0, BigDecimal::of($a)->isGreaterThan($b));
    }

    /**
     * @param string           $a The base number as a string.
     * @param int|float|string $b The number to compare to.
     * @param int              $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThanOrEqualTo(string $a, int|float|string $b, int $c) : void
    {
        self::assertSame($c >= 0, BigDecimal::of($a)->isGreaterThanOrEqualTo($b));
    }

    public static function providerCompareTo() : array
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
     * @param int|float|string $number The number to test.
     * @param int              $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testGetSign(int|float|string $number, int $sign) : void
    {
        self::assertSame($sign, BigDecimal::of($number)->getSign());
    }

    /**
     * @param int|float|string $number The number to test.
     * @param int              $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsZero(int|float|string $number, int $sign) : void
    {
        self::assertSame($sign === 0, BigDecimal::of($number)->isZero());
    }

    /**
     * @param int|float|string $number The number to test.
     * @param int              $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegative(int|float|string $number, int $sign) : void
    {
        self::assertSame($sign < 0, BigDecimal::of($number)->isNegative());
    }

    /**
     * @param int|float|string $number The number to test.
     * @param int              $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegativeOrZero(int|float|string $number, int $sign) : void
    {
        self::assertSame($sign <= 0, BigDecimal::of($number)->isNegativeOrZero());
    }

    /**
     * @param int|float|string $number The number to test.
     * @param int              $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositive(int|float|string $number, int $sign) : void
    {
        self::assertSame($sign > 0, BigDecimal::of($number)->isPositive());
    }

    /**
     * @param int|float|string $number The number to test.
     * @param int              $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositiveOrZero(int|float|string $number, int $sign) : void
    {
        self::assertSame($sign >= 0, BigDecimal::of($number)->isPositiveOrZero());
    }

    public static function providerSign() : array
    {
        return [
            [ 0,  0],
            [-0,  0],
            [ 1,  1],
            [-1, -1],

            [PHP_INT_MAX, 1],
            [PHP_INT_MIN, -1],

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

    #[DataProvider('providerGetPrecision')]
    public function testGetPrecision(string $number, int $precision) : void
    {
        self::assertSame($precision, BigDecimal::of($number)->getPrecision());
        self::assertSame($precision, BigDecimal::of($number)->negated()->getPrecision());
    }

    public static function providerGetPrecision() : array
    {
        return [
            ['0', 0],
            ['0.0', 0],
            ['0.00', 0],
            ['1', 1],
            ['12', 2],
            ['123', 3],
            ['1.2', 2],
            ['1.23', 3],
            ['1.230', 4],
            ['123.456', 6],
            ['0.123', 3],
            ['0.1230', 4],
            ['0.0123', 3],
            ['0.01230', 4],
            ['0.00123', 3],
            ['0.001230', 4],
            ['0.0012300', 5],
            ['1234567890.12345678901234567890123456789012345678901234567890', 60],
            ['0.0000000000000000000000000000000000000000000000000000000000012345', 5],
            ['0.00000000000000000000000000000000000000000000000000000000000123450', 6],
        ];
    }

    /**
     * @param string $number   The number to test.
     * @param string $expected The expected integral value.
     */
    #[DataProvider('providerGetIntegralPart')]
    public function testGetIntegralPart(string $number, string $expected) : void
    {
        self::assertSame($expected, BigDecimal::of($number)->getIntegralPart());
    }

    public static function providerGetIntegralPart() : array
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
     * @param string $number   The number to test.
     * @param string $expected The expected fractional value.
     */
    #[DataProvider('providerGetFractionalPart')]
    public function testGetFractionalPart(string $number, string $expected) : void
    {
        self::assertSame($expected, BigDecimal::of($number)->getFractionalPart());
    }

    public static function providerGetFractionalPart() : array
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
     * @param string $number                   The number to test.
     * @param bool   $hasNonZeroFractionalPart The expected return value.
     */
    #[DataProvider('providerHasNonZeroFractionalPart')]
    public function testHasNonZeroFractionalPart(string $number, bool $hasNonZeroFractionalPart) : void
    {
        self::assertSame($hasNonZeroFractionalPart, BigDecimal::of($number)->hasNonZeroFractionalPart());
    }

    public static function providerHasNonZeroFractionalPart() : array
    {
        return [
            ['1', false],
            ['1.0', false],
            ['1.01', true],
            ['-123456789', false],
            ['-123456789.0000000000000000000000000000000000000000000000000000000', false],
            ['-123456789.00000000000000000000000000000000000000000000000000000001', true]
        ];
    }

    /**
     * @param string $decimal  The number to convert.
     * @param string $expected The expected value.
     */
    #[DataProvider('providerToBigInteger')]
    public function testToBigInteger(string $decimal, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigDecimal::of($decimal)->toBigInteger());
    }

    public static function providerToBigInteger() : array
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
     * @param string $decimal A decimal number with a non-zero fractional part.
     */
    #[DataProvider('providerToBigIntegerThrowsExceptionWhenRoundingNecessary')]
    public function testToBigIntegerThrowsExceptionWhenRoundingNecessary(string $decimal) : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::of($decimal)->toBigInteger();
    }

    public static function providerToBigIntegerThrowsExceptionWhenRoundingNecessary() : array
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
     * @param string $decimal  The decimal number to test.
     * @param string $rational The expected rational number.
     */
    #[DataProvider('providerToBigRational')]
    public function testToBigRational(string $decimal, string $rational) : void
    {
        self::assertBigRationalEquals($rational, BigDecimal::of($decimal)->toBigRational());
    }

    public static function providerToBigRational() : array
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
     * @param int $number The decimal number to test.
     */
    #[DataProvider('providerToInt')]
    public function testToInt(int $number) : void
    {
        self::assertSame($number, BigDecimal::of($number)->toInt());
        self::assertSame($number, BigDecimal::of($number . '.0')->toInt());
    }

    public static function providerToInt() : array
    {
        return [
            [PHP_INT_MIN],
            [-123456789],
            [-1],
            [0],
            [1],
            [123456789],
            [PHP_INT_MAX]
        ];
    }

    /**
     * @param string $number A valid decimal number that cannot safely be converted to a native integer.
     */
    #[DataProvider('providerToIntThrowsException')]
    public function testToIntThrowsException(string $number) : void
    {
        $this->expectException(MathException::class);
        BigDecimal::of($number)->toInt();
    }

    public static function providerToIntThrowsException() : array
    {
        return [
            ['-999999999999999999999999999999'],
            ['9999999999999999999999999999999'],
            ['1.2'],
            ['-1.2'],
        ];
    }

    /**
     * @param string $value The big decimal value.
     * @param float  $float The expected float value.
     */
    #[DataProvider('providerToFloat')]
    public function testToFloat(string $value, float $float) : void
    {
        self::assertSame($float, BigDecimal::of($value)->toFloat());
    }

    public static function providerToFloat() : array
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
     * @param string $unscaledValue The unscaled value.
     * @param int    $scale         The scale.
     * @param string $expected      The expected string representation.
     */
    #[DataProvider('providerToString')]
    public function testToString(string $unscaledValue, int $scale, string $expected) : void
    {
        self::assertSame($expected, (string) BigDecimal::ofUnscaledValue($unscaledValue, $scale));
    }

    public static function providerToString() : array
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

    public function testSerialize() : void
    {
        $value = '-1234567890987654321012345678909876543210123456789';
        $scale = 37;

        $number = BigDecimal::ofUnscaledValue($value, $scale);

        self::assertBigDecimalInternalValues($value, $scale, \unserialize(\serialize($number)));
    }

    public function testDirectCallToUnserialize() : void
    {
        $this->expectException(\LogicException::class);
        BigDecimal::zero()->__unserialize([]);
    }
}
