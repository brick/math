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
use InvalidArgumentException;
use Iterator;
use LogicException;

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
    public function testOf($value, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($value));
    }

    public function providerOf(): Iterator
    {
        yield [0, '0', 0];
        yield [1, '1', 0];
        yield [-1, '-1', 0];
        yield [123_456_789, '123456789', 0];
        yield [-123_456_789, '-123456789', 0];
        yield [PHP_INT_MAX, (string) PHP_INT_MAX, 0];
        yield [PHP_INT_MIN, (string) PHP_INT_MIN, 0];
        yield [0.0, '0', 0];
        yield [0.1, '1', 1];
        yield [1.0, '1', 0];
        yield [1.1, '11', 1];
        yield ['0', '0', 0];
        yield ['+0', '0', 0];
        yield ['-0', '0', 0];
        yield ['00', '0', 0];
        yield ['+00', '0', 0];
        yield ['-00', '0', 0];
        yield ['1', '1', 0];
        yield ['+1', '1', 0];
        yield ['-1', '-1', 0];
        yield ['01', '1', 0];
        yield ['+01', '1', 0];
        yield ['-01', '-1', 0];
        yield ['0.0', '0', 1];
        yield ['+0.0', '0', 1];
        yield ['-0.0', '0', 1];
        yield ['00.0', '0', 1];
        yield ['+00.0', '0', 1];
        yield ['-00.0', '0', 1];
        yield ['.0', '0', 1];
        yield ['.00', '0', 2];
        yield ['.123', '123', 3];
        yield ['+.2', '2', 1];
        yield ['-.33', '-33', 2];
        yield ['1.e2', '100', 0];
        yield ['.1e-1', '1', 2];
        yield ['.1e0', '1', 1];
        yield ['.1e1', '1', 0];
        yield ['.1e2', '10', 0];
        yield ['1.e-2', '1', 2];
        yield ['.1e-2', '1', 3];
        yield ['.12e1', '12', 1];
        yield ['.012e1', '12', 2];
        yield ['-.15e3', '-150', 0];
        yield ['1.', '1', 0];
        yield ['+12.', '12', 0];
        yield ['-123.', '-123', 0];
        yield ['1.0', '10', 1];
        yield ['+1.0', '10', 1];
        yield ['-1.0', '-10', 1];
        yield ['01.0', '10', 1];
        yield ['+01.0', '10', 1];
        yield ['-01.0', '-10', 1];
        yield ['0.1', '1', 1];
        yield ['+0.1', '1', 1];
        yield ['-0.1', '-1', 1];
        yield ['0.10', '10', 2];
        yield ['+0.10', '10', 2];
        yield ['-0.10', '-10', 2];
        yield ['0.010', '10', 3];
        yield ['+0.010', '10', 3];
        yield ['-0.010', '-10', 3];
        yield ['00.1', '1', 1];
        yield ['+00.1', '1', 1];
        yield ['-00.1', '-1', 1];
        yield ['00.10', '10', 2];
        yield ['+00.10', '10', 2];
        yield ['-00.10', '-10', 2];
        yield ['00.010', '10', 3];
        yield ['+00.010', '10', 3];
        yield ['-00.010', '-10', 3];
        yield ['01.1', '11', 1];
        yield ['+01.1', '11', 1];
        yield ['-01.1', '-11', 1];
        yield ['01.010', '1010', 3];
        yield ['+01.010', '1010', 3];
        yield ['-01.010', '-1010', 3];
        yield ['0e-2', '0', 2];
        yield ['0e-1', '0', 1];
        yield ['0e-0', '0', 0];
        yield ['0e0', '0', 0];
        yield ['0e1', '0', 0];
        yield ['0e2', '0', 0];
        yield ['0e+0', '0', 0];
        yield ['0e+1', '0', 0];
        yield ['0e+2', '0', 0];
        yield ['0.0e-2', '0', 3];
        yield ['0.0e-1', '0', 2];
        yield ['0.0e-0', '0', 1];
        yield ['0.0e0', '0', 1];
        yield ['0.0e1', '0', 0];
        yield ['0.0e2', '0', 0];
        yield ['0.0e+0', '0', 1];
        yield ['0.0e+1', '0', 0];
        yield ['0.0e+2', '0', 0];
        yield ['0.1e-2', '1', 3];
        yield ['0.1e-1', '1', 2];
        yield ['0.1e-0', '1', 1];
        yield ['0.1e0', '1', 1];
        yield ['0.1e1', '1', 0];
        yield ['0.1e2', '10', 0];
        yield ['0.1e+0', '1', 1];
        yield ['0.1e+1', '1', 0];
        yield ['0.1e+2', '10', 0];
        yield ['1.23e+011', '123000000000', 0];
        yield ['1.23e-011', '123', 13];
        yield ['0.01e-2', '1', 4];
        yield ['0.01e-1', '1', 3];
        yield ['0.01e-0', '1', 2];
        yield ['0.01e0', '1', 2];
        yield ['0.01e1', '1', 1];
        yield ['0.01e2', '1', 0];
        yield ['0.01e+0', '1', 2];
        yield ['0.01e+1', '1', 1];
        yield ['0.01e+2', '1', 0];
        yield ['0.10e-2', '10', 4];
        yield ['0.10e-1', '10', 3];
        yield ['0.10e-0', '10', 2];
        yield ['0.10e0', '10', 2];
        yield ['0.10e1', '10', 1];
        yield ['0.10e2', '10', 0];
        yield ['0.10e+0', '10', 2];
        yield ['0.10e+1', '10', 1];
        yield ['0.10e+2', '10', 0];
        yield ['00.10e-2', '10', 4];
        yield ['+00.10e-1', '10', 3];
        yield ['-00.10e-0', '-10', 2];
        yield ['00.10e0', '10', 2];
        yield ['+00.10e1', '10', 1];
        yield ['-00.10e2', '-10', 0];
        yield ['00.10e+0', '10', 2];
        yield ['+00.10e+1', '10', 1];
        yield ['-00.10e+2', '-10', 0];
    }

    /**
     * @dataProvider providerOfFloatInDifferentLocales
     */
    public function testOfFloatInDifferentLocales(string $locale): void
    {
        $originalLocale = setlocale(LC_NUMERIC, '0');
        $setLocale = setlocale(LC_NUMERIC, $locale);

        if ($setLocale !== $locale) {
            setlocale(LC_NUMERIC, $originalLocale);
            self::markTestSkipped('Locale ' . $locale . ' is not supported on this system.');
        }

        // Test a large enough number (thousands separator) with decimal digits (decimal separator)
        self::assertSame('2500.5', (string) BigDecimal::of(5001 / 2));

        // Ensure that the locale has been reset to its original value by BigNumber::of()
        self::assertSame($locale, setlocale(LC_NUMERIC, '0'));

        setlocale(LC_NUMERIC, $originalLocale);
    }

    public function providerOfFloatInDifferentLocales(): Iterator
    {
        yield ['C'];
        yield ['en_US.UTF-8'];
        yield ['de_DE.UTF-8'];
        yield ['es_ES'];
        yield ['fr_FR'];
        yield ['fr_FR.iso88591'];
        yield ['fr_FR.iso885915@euro'];
        yield ['fr_FR@euro'];
        yield ['fr_FR.utf8'];
        yield ['ps_AF'];
    }

    /**
     * @dataProvider providerOfInvalidValueThrowsException
     */
    public function testOfInvalidValueThrowsException($value): void
    {
        $this->expectException(NumberFormatException::class);
        BigDecimal::of($value);
    }

    public function providerOfInvalidValueThrowsException(): Iterator
    {
        yield [''];
        yield ['a'];
        yield [' 1'];
        yield ['1 '];
        yield ['..1'];
        yield ['1..'];
        yield ['.1.'];
        yield ['+'];
        yield ['-'];
        yield ['.'];
        yield ['1e'];
        yield ['.e'];
        yield ['.e1'];
        yield ['1e+'];
        yield ['1e-'];
        yield ['+e1'];
        yield ['-e2'];
        yield ['.e3'];
        yield ['+a'];
        yield ['-a'];
        yield ['1e1000000000000000000000000000000'];
        yield ['1e-1000000000000000000000000000000'];
        yield [INF];
        yield [-INF];
        yield [NAN];
    }

    public function testOfBigDecimalReturnsThis(): void
    {
        $decimal = BigDecimal::of(123);

        self::assertSame($decimal, BigDecimal::of($decimal));
    }

    /**
     * @dataProvider providerOfUnscaledValue
     *
     * @param string|int $unscaledValue         The unscaled value of the BigDecimal to create.
     * @param int        $scale                 The scale of the BigDecimal to create.
     * @param string     $expectedUnscaledValue The expected result unscaled value.
     */
    public function testOfUnscaledValue($unscaledValue, int $scale, string $expectedUnscaledValue): void
    {
        $number = BigDecimal::ofUnscaledValue($unscaledValue, $scale);
        self::assertBigDecimalInternalValues($expectedUnscaledValue, $scale, $number);
    }

    public function providerOfUnscaledValue(): Iterator
    {
        yield [123_456_789, 0, '123456789'];
        yield [123_456_789, 1, '123456789'];
        yield [-123_456_789, 0, '-123456789'];
        yield [-123_456_789, 1, '-123456789'];
        yield ['123456789012345678901234567890', 0, '123456789012345678901234567890'];
        yield ['123456789012345678901234567890', 1, '123456789012345678901234567890'];
        yield ['+123456789012345678901234567890', 0, '123456789012345678901234567890'];
        yield ['+123456789012345678901234567890', 1, '123456789012345678901234567890'];
        yield ['-123456789012345678901234567890', 0, '-123456789012345678901234567890'];
        yield ['-123456789012345678901234567890', 1, '-123456789012345678901234567890'];
        yield ['0123456789012345678901234567890', 0, '123456789012345678901234567890'];
        yield ['0123456789012345678901234567890', 1, '123456789012345678901234567890'];
        yield ['+0123456789012345678901234567890', 0, '123456789012345678901234567890'];
        yield ['+0123456789012345678901234567890', 1, '123456789012345678901234567890'];
        yield ['-0123456789012345678901234567890', 0, '-123456789012345678901234567890'];
        yield ['-0123456789012345678901234567890', 1, '-123456789012345678901234567890'];
    }

    public function testOfUnscaledValueWithDefaultScale(): void
    {
        $number = BigDecimal::ofUnscaledValue('123456789');
        self::assertBigDecimalInternalValues('123456789', 0, $number);
    }

    public function testOfUnscaledValueWithNegativeScaleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigDecimal::ofUnscaledValue('0', -1);
    }

    public function testZero(): void
    {
        self::assertBigDecimalInternalValues('0', 0, BigDecimal::zero());
        self::assertSame(BigDecimal::zero(), BigDecimal::zero());
    }

    public function testOne(): void
    {
        self::assertBigDecimalInternalValues('1', 0, BigDecimal::one());
        self::assertSame(BigDecimal::one(), BigDecimal::one());
    }

    public function testTen(): void
    {
        self::assertBigDecimalInternalValues('10', 0, BigDecimal::ten());
        self::assertSame(BigDecimal::ten(), BigDecimal::ten());
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $values The values to compare.
     * @param string $min    The expected minimum value.
     */
    public function testMin(array $values, string $min): void
    {
        self::assertBigDecimalEquals($min, BigDecimal::min(...$values));
    }

    public function providerMin(): Iterator
    {
        yield [[0, 1, -1], '-1'];
        yield [[0, 1, -1, -1.2], '-1.2'];
        yield [['1e30', '123456789123456789123456789', 2e25], '20000000000000000000000000'];
        yield [['1e30', '123456789123456789123456789', 2e26], '123456789123456789123456789'];
        yield [[0, '10', '5989', '-3/3'], '-1'];
        yield [['-0.0000000000000000000000000000001', '0'], '-0.0000000000000000000000000000001'];
        yield [['0.00000000000000000000000000000001', '0'], '0'];
        yield [['-1', '1', '2', '3', '-2973/30'], '-99.1'];
        yield [['999999999999999999999999999.99999999999', '1000000000000000000000000000'],
            '999999999999999999999999999.99999999999',
        ];
        yield [['-999999999999999999999999999.99999999999', '-1000000000000000000000000000'],
            '-1000000000000000000000000000',
        ];
        yield [['9.9e50', '1e50'], '100000000000000000000000000000000000000000000000000'];
        yield [['9.9e50', '1e51'], '990000000000000000000000000000000000000000000000000'];
    }

    public function testMinOfZeroValuesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigDecimal::min();
    }

    public function testMinOfNonDecimalValuesThrowsException(): void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::min(1, '1/3');
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $values The values to compare.
     * @param string $max    The expected maximum value.
     */
    public function testMax(array $values, string $max): void
    {
        self::assertBigDecimalEquals($max, BigDecimal::max(...$values));
    }

    public function providerMax(): Iterator
    {
        yield [[0, 0.9, -1.00], '0.9'];
        yield [[0, 0.01, -1, -1.2], '0.01'];
        yield [[0, 0.01, -1, -1.2, '2e-1'], '0.2'];
        yield [['1e-30', '123456789123456789123456789', 2e25], '123456789123456789123456789'];
        yield [['1e-30', '123456789123456789123456789', 2e26], '200000000000000000000000000'];
        yield [[0, '10', '5989', '-1'], '5989'];
        yield [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1'],
            '5989.000000000000000000000000000000001',
        ];
        yield [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1', '5990'], '5990'];
        yield [['-0.0000000000000000000000000000001', 0], '0'];
        yield [['0.00000000000000000000000000000001', '0'], '0.00000000000000000000000000000001'];
        yield [['-1', '1', '2', '3', '-99.1'], '3'];
        yield [['-1', '1', '2', '3', '-99.1', '31/10'], '3.1'];
        yield [['999999999999999999999999999.99999999999', '1000000000000000000000000000'],
            '1000000000000000000000000000',
        ];
        yield [['-999999999999999999999999999.99999999999', '-1000000000000000000000000000'],
            '-999999999999999999999999999.99999999999',
        ];
        yield [['9.9e50', '1e50'], '990000000000000000000000000000000000000000000000000'];
        yield [['9.9e50', '1e51'], '1000000000000000000000000000000000000000000000000000'];
    }

    public function testMaxOfZeroValuesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigDecimal::max();
    }

    public function testMaxOfNonDecimalValuesThrowsException(): void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::min(1, '3/7');
    }

    /**
     * @dataProvider providerSum
     *
     * @param array  $values The values to add.
     * @param string $sum    The expected sum.
     */
    public function testSum(array $values, string $sum): void
    {
        self::assertBigDecimalEquals($sum, BigDecimal::sum(...$values));
    }

    public function providerSum(): Iterator
    {
        yield [[0, 0.9, -1.00], '-0.1'];
        yield [[0, 0.01, -1, -1.2], '-2.19'];
        yield [[0, 0.01, -1, -1.2, '2e-1'], '-1.99'];
        yield [['1e-30', '123456789123456789123456789', 2e25],
            '143456789123456789123456789.000000000000000000000000000001',
        ];
        yield [['1e-30', '123456789123456789123456789', 2e26],
            '323456789123456789123456789.000000000000000000000000000001',
        ];
        yield [[0, '10', '5989', '-1'], '5998'];
        yield [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1'],
            '11987.000000000000000000000000000000001',
        ];
        yield [[0, '10', '5989', '5989.000000000000000000000000000000001', '-1', '5990'],
            '17977.000000000000000000000000000000001',
        ];
        yield [['-0.0000000000000000000000000000001', 0], '-0.0000000000000000000000000000001'];
        yield [['0.00000000000000000000000000000001', '0'], '0.00000000000000000000000000000001'];
        yield [['-1', '1', '2', '3', '-99.1'], '-94.1'];
        yield [['-1', '1', '2', '3', '-99.1', '31/10'], '-91.0'];
        yield [['999999999999999999999999999.99999999999', '1000000000000000000000000000'],
            '1999999999999999999999999999.99999999999',
        ];
        yield [['-999999999999999999999999999.99999999999', 47, '-1000000000000000000000000000'],
            '-1999999999999999999999999952.99999999999',
        ];
        yield [['9.9e50', '1e50', '-3/2'], '1089999999999999999999999999999999999999999999999998.5'];
        yield [['9.9e50', '-1e-51'],
            '989999999999999999999999999999999999999999999999999.999999999999999999999999999999999999999999999999999',
        ];
    }

    public function testSumOfZeroValuesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigDecimal::sum();
    }

    public function testSumOfNonDecimalValuesThrowsException(): void
    {
        $this->expectException(RoundingNecessaryException::class);
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
    public function testPlus(string $a, string $b, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->plus($b));
    }

    public function providerPlus(): Iterator
    {
        yield ['123', '999', '1122', 0];
        yield ['123', '999.0', '11220', 1];
        yield ['123', '999.00', '112200', 2];
        yield ['123.0', '999', '11220', 1];
        yield ['123.0', '999.0', '11220', 1];
        yield ['123.0', '999.00', '112200', 2];
        yield ['123.00', '999', '112200', 2];
        yield ['123.00', '999.0', '112200', 2];
        yield ['123.00', '999.00', '112200', 2];
        yield ['0', '999', '999', 0];
        yield ['0', '999.0', '9990', 1];
        yield ['0', '999.00', '99900', 2];
        yield ['0.0', '999', '9990', 1];
        yield ['0.0', '999.0', '9990', 1];
        yield ['0.0', '999.00', '99900', 2];
        yield ['0.00', '999', '99900', 2];
        yield ['0.00', '999.0', '99900', 2];
        yield ['0.00', '999.00', '99900', 2];
        yield ['123', '-999', '-876', 0];
        yield ['123', '-999.0', '-8760', 1];
        yield ['123', '-999.00', '-87600', 2];
        yield ['123.0', '-999', '-8760', 1];
        yield ['123.0', '-999.0', '-8760', 1];
        yield ['123.0', '-999.00', '-87600', 2];
        yield ['123.00', '-999', '-87600', 2];
        yield ['123.00', '-999.0', '-87600', 2];
        yield ['123.00', '-999.00', '-87600', 2];
        yield ['-123', '999', '876', 0];
        yield ['-123', '999.0', '8760', 1];
        yield ['-123', '999.00', '87600', 2];
        yield ['-123.0', '999', '8760', 1];
        yield ['-123.0', '999.0', '8760', 1];
        yield ['-123.0', '999.00', '87600', 2];
        yield ['-123.00', '999', '87600', 2];
        yield ['-123.00', '999.0', '87600', 2];
        yield ['-123.00', '999.00', '87600', 2];
        yield ['-123', '-999', '-1122', 0];
        yield ['-123', '-999.0', '-11220', 1];
        yield ['-123', '-999.00', '-112200', 2];
        yield ['-123.0', '-999', '-11220', 1];
        yield ['-123.0', '-999.0', '-11220', 1];
        yield ['-123.0', '-999.00', '-112200', 2];
        yield ['-123.00', '-999', '-112200', 2];
        yield ['-123.00', '-999.0', '-112200', 2];
        yield ['-123.00', '-999.00', '-112200', 2];
        yield [
            '23487837847837428335.322387091',
            '309049304233535454687656.2392',
            '309072792071383292115991561587091',
            9,
        ];
        yield ['-234878378478328335.322387091', '309049304233535154687656.232', '309049069355156676359320909612909', 9];
        yield ['234878378478328335.3227091', '-3090495154687656.231343344452', '231787883323640679091365755548', 12];
        yield ['-23487837847833435.3231', '-3090495154687656.231343344452', '-26578333002521091554443344452', 12];
        yield ['1234568798347983.2334899238921', '0', '12345687983479832334899238921', 13];
        yield ['-0.00223287647368738736428467863784', '0.000', '-223287647368738736428467863784', 32];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param string $a             The base number.
     * @param string $b             The number to subtract.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    public function testMinus(string $a, string $b, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->minus($b));
    }

    public function providerMinus(): Iterator
    {
        yield ['123', '999', '-876', 0];
        yield ['123', '999.0', '-8760', 1];
        yield ['123', '999.00', '-87600', 2];
        yield ['123.0', '999', '-8760', 1];
        yield ['123.0', '999.0', '-8760', 1];
        yield ['123.0', '999.00', '-87600', 2];
        yield ['123.00', '999', '-87600', 2];
        yield ['123.00', '999.0', '-87600', 2];
        yield ['123.00', '999.00', '-87600', 2];
        yield ['0', '999', '-999', 0];
        yield ['0', '999.0', '-9990', 1];
        yield ['123', '-999', '1122', 0];
        yield ['123', '-999.0', '11220', 1];
        yield ['123', '-999.00', '112200', 2];
        yield ['123.0', '-999', '11220', 1];
        yield ['123.0', '-999.0', '11220', 1];
        yield ['123.0', '-999.00', '112200', 2];
        yield ['123.00', '-999', '112200', 2];
        yield ['123.00', '-999.0', '112200', 2];
        yield ['123.00', '-999.00', '112200', 2];
        yield ['-123', '999', '-1122', 0];
        yield ['-123', '999.0', '-11220', 1];
        yield ['-123', '999.00', '-112200', 2];
        yield ['-123.0', '999', '-11220', 1];
        yield ['-123.0', '999.0', '-11220', 1];
        yield ['-123.0', '999.00', '-112200', 2];
        yield ['-123.00', '999', '-112200', 2];
        yield ['-123.00', '999.0', '-112200', 2];
        yield ['-123.00', '999.00', '-112200', 2];
        yield ['-123', '-999', '876', 0];
        yield ['-123', '-999.0', '8760', 1];
        yield ['-123', '-999.00', '87600', 2];
        yield ['-123.0', '-999', '8760', 1];
        yield ['-123.0', '-999.0', '8760', 1];
        yield ['-123.0', '-999.00', '87600', 2];
        yield ['-123.00', '-999', '87600', 2];
        yield ['-123.00', '-999.0', '87600', 2];
        yield ['-123.00', '-999.00', '87600', 2];
        yield ['234878378477428335.3223334343487091', '309049304233536.2392', '2345693291731947990831334343487091', 16];
        yield ['-2348783784774335.32233343434891', '309049304233536.233392', '-265783308900787155572543434891', 14];
        yield ['2348783784774335.323232342791', '-309049304233536.556172', '2657833089007871879404342791', 12];
        yield ['-2348783784774335.3232342791', '-309049304233536.556172', '-20397344805407987670622791', 10];
        yield ['1234568798347983.2334899238921', '0', '12345687983479832334899238921', 13];
        yield ['0', '1234568798347983.2334899238921', '-12345687983479832334899238921', 13];
        yield ['-0.00223287647368738736428467863784', '0.000', '-223287647368738736428467863784', 32];
    }

    /**
     * @dataProvider providerMultipliedBy
     *
     * @param string $a             The base number.
     * @param string $b             The number to multiply.
     * @param string $unscaledValue The expected unscaled value.
     * @param int    $scale         The expected scale.
     */
    public function testMultipliedBy(string $a, string $b, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($a)->multipliedBy($b));
    }

    public function providerMultipliedBy(): Iterator
    {
        yield ['123', '999', '122877', 0];
        yield ['123', '999.0', '1228770', 1];
        yield ['123', '999.00', '12287700', 2];
        yield ['123.0', '999', '1228770', 1];
        yield ['123.0', '999.0', '12287700', 2];
        yield ['123.0', '999.00', '122877000', 3];
        yield ['123.00', '999', '12287700', 2];
        yield ['123.00', '999.0', '122877000', 3];
        yield ['123.00', '999.00', '1228770000', 4];
        yield ['123.0', '0.1', '1230', 2];
        yield ['123.0', '0.01', '1230', 3];
        yield ['123.1', '0.01', '1231', 3];
        yield ['123.1', '0.001', '1231', 4];
        yield ['123', '-999', '-122877', 0];
        yield ['123', '-999.0', '-1228770', 1];
        yield ['123', '-999.00', '-12287700', 2];
        yield ['123.0', '-999', '-1228770', 1];
        yield ['123.0', '-999.0', '-12287700', 2];
        yield ['123.0', '-999.00', '-122877000', 3];
        yield ['123.00', '-999', '-12287700', 2];
        yield ['123.00', '-999.0', '-122877000', 3];
        yield ['123.00', '-999.00', '-1228770000', 4];
        yield ['-123', '999', '-122877', 0];
        yield ['-123', '999.0', '-1228770', 1];
        yield ['-123', '999.00', '-12287700', 2];
        yield ['-123.0', '999', '-1228770', 1];
        yield ['-123.0', '999.0', '-12287700', 2];
        yield ['-123.0', '999.00', '-122877000', 3];
        yield ['-123.00', '999', '-12287700', 2];
        yield ['-123.00', '999.0', '-122877000', 3];
        yield ['-123.00', '999.00', '-1228770000', 4];
        yield ['-123', '-999', '122877', 0];
        yield ['-123', '-999.0', '1228770', 1];
        yield ['-123', '-999.00', '12287700', 2];
        yield ['-123.0', '-999', '1228770', 1];
        yield ['-123.0', '-999.0', '12287700', 2];
        yield ['-123.0', '-999.00', '122877000', 3];
        yield ['-123.00', '-999', '12287700', 2];
        yield ['-123.00', '-999.0', '122877000', 3];
        yield ['-123.00', '-999.00', '1228770000', 4];
        yield ['1', '999', '999', 0];
        yield ['1', '999.0', '9990', 1];
        yield ['1', '999.00', '99900', 2];
        yield ['1.0', '999', '9990', 1];
        yield ['1.0', '999.0', '99900', 2];
        yield ['1.0', '999.00', '999000', 3];
        yield ['1.00', '999', '99900', 2];
        yield ['1.00', '999.0', '999000', 3];
        yield ['1.00', '999.00', '9990000', 4];
        yield ['123', '1', '123', 0];
        yield ['123', '1.0', '1230', 1];
        yield ['123', '1.00', '12300', 2];
        yield ['123.0', '1', '1230', 1];
        yield ['123.0', '1.0', '12300', 2];
        yield ['123.0', '1.00', '123000', 3];
        yield ['123.00', '1', '12300', 2];
        yield ['123.00', '1.0', '123000', 3];
        yield ['123.00', '1.00', '1230000', 4];
        yield ['0', '999', '0', 0];
        yield ['0', '999.0', '0', 1];
        yield ['0', '999.00', '0', 2];
        yield ['0.0', '999', '0', 1];
        yield ['0.0', '999.0', '0', 2];
        yield ['0.0', '999.00', '0', 3];
        yield ['0.00', '999', '0', 2];
        yield ['0.00', '999.0', '0', 3];
        yield ['0.00', '999.00', '0', 4];
        yield ['123', '0', '0', 0];
        yield ['123', '0.0', '0', 1];
        yield ['123', '0.00', '0', 2];
        yield ['123.0', '0', '0', 1];
        yield ['123.0', '0.0', '0', 2];
        yield ['123.0', '0.00', '0', 3];
        yield ['123.00', '0', '0', 2];
        yield ['123.00', '0.0', '0', 3];
        yield ['123.00', '0.00', '0', 4];
        yield ['589252.156111130', '999.2563989942545241223454', '5888139876152080735720775399923986443020', 31];
        yield ['-589252.15611130', '999.256398994254524122354', '-58881398761537794715991163083004200020', 29];
        yield ['589252.1561113', '-99.256398994254524122354', '-584870471152079471599116308300420002', 28];
        yield ['-58952.156111', '-9.256398994254524122357', '545684678534996098129205129273627', 27];
        yield ['0.1235437849158495728979344999999999999', '1', '1235437849158495728979344999999999999', 37];
        yield ['-1.324985980890283098409328999999999999', '1', '-1324985980890283098409328999999999999', 36];
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
    public function testDividedBy(
        string $a,
        string $b,
        ?int $scale,
        int $roundingMode,
        string $unscaledValue,
        int $expectedScale
    ): void {
        $decimal = BigDecimal::of($a)->dividedBy($b, $scale, $roundingMode);
        self::assertBigDecimalInternalValues($unscaledValue, $expectedScale, $decimal);
    }

    public function providerDividedBy(): Iterator
    {
        yield ['7', '0.2', 0, RoundingMode::UNNECESSARY, '35', 0];
        yield ['7', '-0.2', 0, RoundingMode::UNNECESSARY, '-35', 0];
        yield ['-7', '0.2', 0, RoundingMode::UNNECESSARY, '-35', 0];
        yield ['-7', '-0.2', 0, RoundingMode::UNNECESSARY, '35', 0];
        yield ['1234567890123456789', '0.01', 0, RoundingMode::UNNECESSARY, '123456789012345678900', 0];
        yield ['1234567890123456789', '0.010', 0, RoundingMode::UNNECESSARY, '123456789012345678900', 0];
        yield ['1324794783847839472983.343898', '1', 6, RoundingMode::UNNECESSARY, '1324794783847839472983343898', 6];
        yield ['-32479478384783947298.3343898', '1', 7, RoundingMode::UNNECESSARY, '-324794783847839472983343898', 7];
        yield ['1.5', '2', 2, RoundingMode::UNNECESSARY, '75', 2];
        yield ['1.5', '3', null, RoundingMode::UNNECESSARY, '5', 1];
        yield ['0.123456789', '0.00244140625', 10, RoundingMode::UNNECESSARY, '505679007744', 10];
        yield ['1.234', '123.456', 50, RoundingMode::DOWN, '999546397096941420425090720580611715914981855883', 50];
        yield ['1', '3', 10, RoundingMode::UP, '3333333334', 10];
        yield ['0.124', '0.2', 3, RoundingMode::UNNECESSARY, '620', 3];
        yield ['0.124', '2', 3, RoundingMode::UNNECESSARY, '62', 3];
    }

    /**
     * @dataProvider providerDividedByByZeroThrowsException
     *
     * @param string|number $zero
     */
    public function testDividedByByZeroThrowsException($zero): void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1)->dividedBy($zero, 0);
    }

    public function providerDividedByByZeroThrowsException(): Iterator
    {
        yield [0];
        yield [0.0];
        yield ['0'];
        yield ['0.0'];
        yield ['0.00'];
    }

    /**
     * @dataProvider providerExactlyDividedBy
     *
     * @param string|number $number   The number to divide.
     * @param string|number $divisor  The divisor.
     * @param string        $expected The expected result, or a class name if an exception is expected.
     */
    public function testExactlyDividedBy($number, $divisor, string $expected): void
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

    public function providerExactlyDividedBy(): Iterator
    {
        yield [1, 1, '1'];
        yield ['1.0', '1.00', '1'];
        yield [1, 2, '0.5'];
        yield [1, 3, RoundingNecessaryException::class];
        yield [1, 4, '0.25'];
        yield [1, 5, '0.2'];
        yield [1, 6, RoundingNecessaryException::class];
        yield [1, 7, RoundingNecessaryException::class];
        yield [1, 8, '0.125'];
        yield [1, 9, RoundingNecessaryException::class];
        yield [1, 10, '0.1'];
        yield ['1.0', 2, '0.5'];
        yield ['1.00', 2, '0.5'];
        yield ['1.0000', 8, '0.125'];
        yield [1, '4.000', '0.25'];
        yield ['1', '0.125', '8'];
        yield ['1.0', '0.125', '8'];
        yield ['1234.5678', '2', '617.2839'];
        yield ['1234.5678', '4', '308.64195'];
        yield ['1234.5678', '8', '154.320975'];
        yield ['1234.5678', '6.4', '192.90121875'];
        yield ['7', '3125', '0.00224'];
        yield [
            '4849709849456546549849846510128399',
            '18014398509481984',
            '269212976880902984.935786476657271160117801400701864622533321380615234375',
        ];
        yield [
            '4849709849456546549849846510128399',
            '-18014398509481984',
            '-269212976880902984.935786476657271160117801400701864622533321380615234375',
        ];
        yield [
            '-4849709849456546549849846510128399',
            '18014398509481984',
            '-269212976880902984.935786476657271160117801400701864622533321380615234375',
        ];
        yield [
            '-4849709849456546549849846510128399',
            '-18014398509481984',
            '269212976880902984.935786476657271160117801400701864622533321380615234375',
        ];
        yield ['123', '0', DivisionByZeroException::class];
        yield [-789, '0.0', DivisionByZeroException::class];
    }

    public function testExactlyDividedByZero(): void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1)->exactlyDividedBy(0);
    }

    /**
     * @dataProvider providerDividedByWithRoundingNecessaryThrowsException
     *
     * @param string $a     The base number.
     * @param string $b     The number to divide by.
     * @param int    $scale The desired scale.
     */
    public function testDividedByWithRoundingNecessaryThrowsException(string $a, string $b, int $scale): void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::of($a)->dividedBy($b, $scale);
    }

    public function providerDividedByWithRoundingNecessaryThrowsException(): Iterator
    {
        yield ['1.234', '123.456', 3];
        yield ['7', '2', 0];
        yield ['7', '3', 100];
    }

    public function testDividedByWithNegativeScaleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigDecimal::of(1)->dividedBy(2, -1);
    }

    public function testDividedByWithInvalidRoundingModeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
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
     * @doesNotPerformAssertions
     */
    public function testRoundingMode(
        int $roundingMode,
        string $number,
        ?string $two,
        ?string $one,
        ?string $zero
    ): void {
        $number = BigDecimal::of($number);
        $this->doTestRoundingMode($roundingMode, $number, '1', $two, $one, $zero);
        $this->doTestRoundingMode($roundingMode, $number->negated(), '-1', $two, $one, $zero);
    }

    public function providerRoundingMode(): Iterator
    {
        yield [RoundingMode::UP, '3.501', '351', '36', '4'];
        yield [RoundingMode::UP, '3.500', '350', '35', '4'];
        yield [RoundingMode::UP, '3.499', '350', '35', '4'];
        yield [RoundingMode::UP, '3.001', '301', '31', '4'];
        yield [RoundingMode::UP, '3.000', '300', '30', '3'];
        yield [RoundingMode::UP, '2.999', '300', '30', '3'];
        yield [RoundingMode::UP, '2.501', '251', '26', '3'];
        yield [RoundingMode::UP, '2.500', '250', '25', '3'];
        yield [RoundingMode::UP, '2.499', '250', '25', '3'];
        yield [RoundingMode::UP, '2.001', '201', '21', '3'];
        yield [RoundingMode::UP, '2.000', '200', '20', '2'];
        yield [RoundingMode::UP, '1.999', '200', '20', '2'];
        yield [RoundingMode::UP, '1.501', '151', '16', '2'];
        yield [RoundingMode::UP, '1.500', '150', '15', '2'];
        yield [RoundingMode::UP, '1.499', '150', '15', '2'];
        yield [RoundingMode::UP, '1.001', '101', '11', '2'];
        yield [RoundingMode::UP, '1.000', '100', '10', '1'];
        yield [RoundingMode::UP, '0.999', '100', '10', '1'];
        yield [RoundingMode::UP, '0.501', '51', '6', '1'];
        yield [RoundingMode::UP, '0.500', '50', '5', '1'];
        yield [RoundingMode::UP, '0.499', '50', '5', '1'];
        yield [RoundingMode::UP, '0.001', '1', '1', '1'];
        yield [RoundingMode::UP, '0.000', '0', '0', '0'];
        yield [RoundingMode::UP, '-0.001', '-1', '-1', '-1'];
        yield [RoundingMode::UP, '-0.499', '-50', '-5', '-1'];
        yield [RoundingMode::UP, '-0.500', '-50', '-5', '-1'];
        yield [RoundingMode::UP, '-0.501', '-51', '-6', '-1'];
        yield [RoundingMode::UP, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::UP, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::UP, '-1.001', '-101', '-11', '-2'];
        yield [RoundingMode::UP, '-1.499', '-150', '-15', '-2'];
        yield [RoundingMode::UP, '-1.500', '-150', '-15', '-2'];
        yield [RoundingMode::UP, '-1.501', '-151', '-16', '-2'];
        yield [RoundingMode::UP, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::UP, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::UP, '-2.001', '-201', '-21', '-3'];
        yield [RoundingMode::UP, '-2.499', '-250', '-25', '-3'];
        yield [RoundingMode::UP, '-2.500', '-250', '-25', '-3'];
        yield [RoundingMode::UP, '-2.501', '-251', '-26', '-3'];
        yield [RoundingMode::UP, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::UP, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::UP, '-3.001', '-301', '-31', '-4'];
        yield [RoundingMode::UP, '-3.499', '-350', '-35', '-4'];
        yield [RoundingMode::UP, '-3.500', '-350', '-35', '-4'];
        yield [RoundingMode::UP, '-3.501', '-351', '-36', '-4'];
        yield [RoundingMode::DOWN, '3.501', '350', '35', '3'];
        yield [RoundingMode::DOWN, '3.500', '350', '35', '3'];
        yield [RoundingMode::DOWN, '3.499', '349', '34', '3'];
        yield [RoundingMode::DOWN, '3.001', '300', '30', '3'];
        yield [RoundingMode::DOWN, '3.000', '300', '30', '3'];
        yield [RoundingMode::DOWN, '2.999', '299', '29', '2'];
        yield [RoundingMode::DOWN, '2.501', '250', '25', '2'];
        yield [RoundingMode::DOWN, '2.500', '250', '25', '2'];
        yield [RoundingMode::DOWN, '2.499', '249', '24', '2'];
        yield [RoundingMode::DOWN, '2.001', '200', '20', '2'];
        yield [RoundingMode::DOWN, '2.000', '200', '20', '2'];
        yield [RoundingMode::DOWN, '1.999', '199', '19', '1'];
        yield [RoundingMode::DOWN, '1.501', '150', '15', '1'];
        yield [RoundingMode::DOWN, '1.500', '150', '15', '1'];
        yield [RoundingMode::DOWN, '1.499', '149', '14', '1'];
        yield [RoundingMode::DOWN, '1.001', '100', '10', '1'];
        yield [RoundingMode::DOWN, '1.000', '100', '10', '1'];
        yield [RoundingMode::DOWN, '0.999', '99', '9', '0'];
        yield [RoundingMode::DOWN, '0.501', '50', '5', '0'];
        yield [RoundingMode::DOWN, '0.500', '50', '5', '0'];
        yield [RoundingMode::DOWN, '0.499', '49', '4', '0'];
        yield [RoundingMode::DOWN, '0.001', '0', '0', '0'];
        yield [RoundingMode::DOWN, '0.000', '0', '0', '0'];
        yield [RoundingMode::DOWN, '-0.001', '0', '0', '0'];
        yield [RoundingMode::DOWN, '-0.499', '-49', '-4', '0'];
        yield [RoundingMode::DOWN, '-0.500', '-50', '-5', '0'];
        yield [RoundingMode::DOWN, '-0.501', '-50', '-5', '0'];
        yield [RoundingMode::DOWN, '-0.999', '-99', '-9', '0'];
        yield [RoundingMode::DOWN, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::DOWN, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::DOWN, '-1.499', '-149', '-14', '-1'];
        yield [RoundingMode::DOWN, '-1.500', '-150', '-15', '-1'];
        yield [RoundingMode::DOWN, '-1.501', '-150', '-15', '-1'];
        yield [RoundingMode::DOWN, '-1.999', '-199', '-19', '-1'];
        yield [RoundingMode::DOWN, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::DOWN, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::DOWN, '-2.499', '-249', '-24', '-2'];
        yield [RoundingMode::DOWN, '-2.500', '-250', '-25', '-2'];
        yield [RoundingMode::DOWN, '-2.501', '-250', '-25', '-2'];
        yield [RoundingMode::DOWN, '-2.999', '-299', '-29', '-2'];
        yield [RoundingMode::DOWN, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::DOWN, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::DOWN, '-3.499', '-349', '-34', '-3'];
        yield [RoundingMode::DOWN, '-3.500', '-350', '-35', '-3'];
        yield [RoundingMode::DOWN, '-3.501', '-350', '-35', '-3'];
        yield [RoundingMode::CEILING, '3.501', '351', '36', '4'];
        yield [RoundingMode::CEILING, '3.500', '350', '35', '4'];
        yield [RoundingMode::CEILING, '3.499', '350', '35', '4'];
        yield [RoundingMode::CEILING, '3.001', '301', '31', '4'];
        yield [RoundingMode::CEILING, '3.000', '300', '30', '3'];
        yield [RoundingMode::CEILING, '2.999', '300', '30', '3'];
        yield [RoundingMode::CEILING, '2.501', '251', '26', '3'];
        yield [RoundingMode::CEILING, '2.500', '250', '25', '3'];
        yield [RoundingMode::CEILING, '2.499', '250', '25', '3'];
        yield [RoundingMode::CEILING, '2.001', '201', '21', '3'];
        yield [RoundingMode::CEILING, '2.000', '200', '20', '2'];
        yield [RoundingMode::CEILING, '1.999', '200', '20', '2'];
        yield [RoundingMode::CEILING, '1.501', '151', '16', '2'];
        yield [RoundingMode::CEILING, '1.500', '150', '15', '2'];
        yield [RoundingMode::CEILING, '1.499', '150', '15', '2'];
        yield [RoundingMode::CEILING, '1.001', '101', '11', '2'];
        yield [RoundingMode::CEILING, '1.000', '100', '10', '1'];
        yield [RoundingMode::CEILING, '0.999', '100', '10', '1'];
        yield [RoundingMode::CEILING, '0.501', '51', '6', '1'];
        yield [RoundingMode::CEILING, '0.500', '50', '5', '1'];
        yield [RoundingMode::CEILING, '0.499', '50', '5', '1'];
        yield [RoundingMode::CEILING, '0.001', '1', '1', '1'];
        yield [RoundingMode::CEILING, '0.000', '0', '0', '0'];
        yield [RoundingMode::CEILING, '-0.001', '0', '0', '0'];
        yield [RoundingMode::CEILING, '-0.499', '-49', '-4', '0'];
        yield [RoundingMode::CEILING, '-0.500', '-50', '-5', '0'];
        yield [RoundingMode::CEILING, '-0.501', '-50', '-5', '0'];
        yield [RoundingMode::CEILING, '-0.999', '-99', '-9', '0'];
        yield [RoundingMode::CEILING, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::CEILING, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::CEILING, '-1.499', '-149', '-14', '-1'];
        yield [RoundingMode::CEILING, '-1.500', '-150', '-15', '-1'];
        yield [RoundingMode::CEILING, '-1.501', '-150', '-15', '-1'];
        yield [RoundingMode::CEILING, '-1.999', '-199', '-19', '-1'];
        yield [RoundingMode::CEILING, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::CEILING, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::CEILING, '-2.499', '-249', '-24', '-2'];
        yield [RoundingMode::CEILING, '-2.500', '-250', '-25', '-2'];
        yield [RoundingMode::CEILING, '-2.501', '-250', '-25', '-2'];
        yield [RoundingMode::CEILING, '-2.999', '-299', '-29', '-2'];
        yield [RoundingMode::CEILING, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::CEILING, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::CEILING, '-3.499', '-349', '-34', '-3'];
        yield [RoundingMode::CEILING, '-3.500', '-350', '-35', '-3'];
        yield [RoundingMode::CEILING, '-3.501', '-350', '-35', '-3'];
        yield [RoundingMode::FLOOR, '3.501', '350', '35', '3'];
        yield [RoundingMode::FLOOR, '3.500', '350', '35', '3'];
        yield [RoundingMode::FLOOR, '3.499', '349', '34', '3'];
        yield [RoundingMode::FLOOR, '3.001', '300', '30', '3'];
        yield [RoundingMode::FLOOR, '3.000', '300', '30', '3'];
        yield [RoundingMode::FLOOR, '2.999', '299', '29', '2'];
        yield [RoundingMode::FLOOR, '2.501', '250', '25', '2'];
        yield [RoundingMode::FLOOR, '2.500', '250', '25', '2'];
        yield [RoundingMode::FLOOR, '2.499', '249', '24', '2'];
        yield [RoundingMode::FLOOR, '2.001', '200', '20', '2'];
        yield [RoundingMode::FLOOR, '2.000', '200', '20', '2'];
        yield [RoundingMode::FLOOR, '1.999', '199', '19', '1'];
        yield [RoundingMode::FLOOR, '1.501', '150', '15', '1'];
        yield [RoundingMode::FLOOR, '1.500', '150', '15', '1'];
        yield [RoundingMode::FLOOR, '1.499', '149', '14', '1'];
        yield [RoundingMode::FLOOR, '1.001', '100', '10', '1'];
        yield [RoundingMode::FLOOR, '1.000', '100', '10', '1'];
        yield [RoundingMode::FLOOR, '0.999', '99', '9', '0'];
        yield [RoundingMode::FLOOR, '0.501', '50', '5', '0'];
        yield [RoundingMode::FLOOR, '0.500', '50', '5', '0'];
        yield [RoundingMode::FLOOR, '0.499', '49', '4', '0'];
        yield [RoundingMode::FLOOR, '0.001', '0', '0', '0'];
        yield [RoundingMode::FLOOR, '0.000', '0', '0', '0'];
        yield [RoundingMode::FLOOR, '-0.001', '-1', '-1', '-1'];
        yield [RoundingMode::FLOOR, '-0.499', '-50', '-5', '-1'];
        yield [RoundingMode::FLOOR, '-0.500', '-50', '-5', '-1'];
        yield [RoundingMode::FLOOR, '-0.501', '-51', '-6', '-1'];
        yield [RoundingMode::FLOOR, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::FLOOR, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::FLOOR, '-1.001', '-101', '-11', '-2'];
        yield [RoundingMode::FLOOR, '-1.499', '-150', '-15', '-2'];
        yield [RoundingMode::FLOOR, '-1.500', '-150', '-15', '-2'];
        yield [RoundingMode::FLOOR, '-1.501', '-151', '-16', '-2'];
        yield [RoundingMode::FLOOR, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::FLOOR, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::FLOOR, '-2.001', '-201', '-21', '-3'];
        yield [RoundingMode::FLOOR, '-2.499', '-250', '-25', '-3'];
        yield [RoundingMode::FLOOR, '-2.500', '-250', '-25', '-3'];
        yield [RoundingMode::FLOOR, '-2.501', '-251', '-26', '-3'];
        yield [RoundingMode::FLOOR, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::FLOOR, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::FLOOR, '-3.001', '-301', '-31', '-4'];
        yield [RoundingMode::FLOOR, '-3.499', '-350', '-35', '-4'];
        yield [RoundingMode::FLOOR, '-3.500', '-350', '-35', '-4'];
        yield [RoundingMode::FLOOR, '-3.501', '-351', '-36', '-4'];
        yield [RoundingMode::HALF_UP, '3.501', '350', '35', '4'];
        yield [RoundingMode::HALF_UP, '3.500', '350', '35', '4'];
        yield [RoundingMode::HALF_UP, '3.499', '350', '35', '3'];
        yield [RoundingMode::HALF_UP, '3.001', '300', '30', '3'];
        yield [RoundingMode::HALF_UP, '3.000', '300', '30', '3'];
        yield [RoundingMode::HALF_UP, '2.999', '300', '30', '3'];
        yield [RoundingMode::HALF_UP, '2.501', '250', '25', '3'];
        yield [RoundingMode::HALF_UP, '2.500', '250', '25', '3'];
        yield [RoundingMode::HALF_UP, '2.499', '250', '25', '2'];
        yield [RoundingMode::HALF_UP, '2.001', '200', '20', '2'];
        yield [RoundingMode::HALF_UP, '2.000', '200', '20', '2'];
        yield [RoundingMode::HALF_UP, '1.999', '200', '20', '2'];
        yield [RoundingMode::HALF_UP, '1.501', '150', '15', '2'];
        yield [RoundingMode::HALF_UP, '1.500', '150', '15', '2'];
        yield [RoundingMode::HALF_UP, '1.499', '150', '15', '1'];
        yield [RoundingMode::HALF_UP, '1.001', '100', '10', '1'];
        yield [RoundingMode::HALF_UP, '1.000', '100', '10', '1'];
        yield [RoundingMode::HALF_UP, '0.999', '100', '10', '1'];
        yield [RoundingMode::HALF_UP, '0.501', '50', '5', '1'];
        yield [RoundingMode::HALF_UP, '0.500', '50', '5', '1'];
        yield [RoundingMode::HALF_UP, '0.499', '50', '5', '0'];
        yield [RoundingMode::HALF_UP, '0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_UP, '0.000', '0', '0', '0'];
        yield [RoundingMode::HALF_UP, '-0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_UP, '-0.499', '-50', '-5', '0'];
        yield [RoundingMode::HALF_UP, '-0.500', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_UP, '-0.501', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_UP, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_UP, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_UP, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_UP, '-1.499', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_UP, '-1.500', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_UP, '-1.501', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_UP, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_UP, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_UP, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_UP, '-2.499', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_UP, '-2.500', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_UP, '-2.501', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_UP, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_UP, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_UP, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_UP, '-3.499', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_UP, '-3.500', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_UP, '-3.501', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_DOWN, '3.501', '350', '35', '4'];
        yield [RoundingMode::HALF_DOWN, '3.500', '350', '35', '3'];
        yield [RoundingMode::HALF_DOWN, '3.499', '350', '35', '3'];
        yield [RoundingMode::HALF_DOWN, '3.001', '300', '30', '3'];
        yield [RoundingMode::HALF_DOWN, '3.000', '300', '30', '3'];
        yield [RoundingMode::HALF_DOWN, '2.999', '300', '30', '3'];
        yield [RoundingMode::HALF_DOWN, '2.501', '250', '25', '3'];
        yield [RoundingMode::HALF_DOWN, '2.500', '250', '25', '2'];
        yield [RoundingMode::HALF_DOWN, '2.499', '250', '25', '2'];
        yield [RoundingMode::HALF_DOWN, '2.001', '200', '20', '2'];
        yield [RoundingMode::HALF_DOWN, '2.000', '200', '20', '2'];
        yield [RoundingMode::HALF_DOWN, '1.999', '200', '20', '2'];
        yield [RoundingMode::HALF_DOWN, '1.501', '150', '15', '2'];
        yield [RoundingMode::HALF_DOWN, '1.500', '150', '15', '1'];
        yield [RoundingMode::HALF_DOWN, '1.499', '150', '15', '1'];
        yield [RoundingMode::HALF_DOWN, '1.001', '100', '10', '1'];
        yield [RoundingMode::HALF_DOWN, '1.000', '100', '10', '1'];
        yield [RoundingMode::HALF_DOWN, '0.999', '100', '10', '1'];
        yield [RoundingMode::HALF_DOWN, '0.501', '50', '5', '1'];
        yield [RoundingMode::HALF_DOWN, '0.500', '50', '5', '0'];
        yield [RoundingMode::HALF_DOWN, '0.499', '50', '5', '0'];
        yield [RoundingMode::HALF_DOWN, '0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_DOWN, '0.000', '0', '0', '0'];
        yield [RoundingMode::HALF_DOWN, '-0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_DOWN, '-0.499', '-50', '-5', '0'];
        yield [RoundingMode::HALF_DOWN, '-0.500', '-50', '-5', '0'];
        yield [RoundingMode::HALF_DOWN, '-0.501', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_DOWN, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_DOWN, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_DOWN, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_DOWN, '-1.499', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_DOWN, '-1.500', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_DOWN, '-1.501', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_DOWN, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_DOWN, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_DOWN, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_DOWN, '-2.499', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_DOWN, '-2.500', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_DOWN, '-2.501', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_DOWN, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_DOWN, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_DOWN, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_DOWN, '-3.499', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_DOWN, '-3.500', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_DOWN, '-3.501', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_CEILING, '3.501', '350', '35', '4'];
        yield [RoundingMode::HALF_CEILING, '3.500', '350', '35', '4'];
        yield [RoundingMode::HALF_CEILING, '3.499', '350', '35', '3'];
        yield [RoundingMode::HALF_CEILING, '3.001', '300', '30', '3'];
        yield [RoundingMode::HALF_CEILING, '3.000', '300', '30', '3'];
        yield [RoundingMode::HALF_CEILING, '2.999', '300', '30', '3'];
        yield [RoundingMode::HALF_CEILING, '2.501', '250', '25', '3'];
        yield [RoundingMode::HALF_CEILING, '2.500', '250', '25', '3'];
        yield [RoundingMode::HALF_CEILING, '2.499', '250', '25', '2'];
        yield [RoundingMode::HALF_CEILING, '2.001', '200', '20', '2'];
        yield [RoundingMode::HALF_CEILING, '2.000', '200', '20', '2'];
        yield [RoundingMode::HALF_CEILING, '1.999', '200', '20', '2'];
        yield [RoundingMode::HALF_CEILING, '1.501', '150', '15', '2'];
        yield [RoundingMode::HALF_CEILING, '1.500', '150', '15', '2'];
        yield [RoundingMode::HALF_CEILING, '1.499', '150', '15', '1'];
        yield [RoundingMode::HALF_CEILING, '1.001', '100', '10', '1'];
        yield [RoundingMode::HALF_CEILING, '1.000', '100', '10', '1'];
        yield [RoundingMode::HALF_CEILING, '0.999', '100', '10', '1'];
        yield [RoundingMode::HALF_CEILING, '0.501', '50', '5', '1'];
        yield [RoundingMode::HALF_CEILING, '0.500', '50', '5', '1'];
        yield [RoundingMode::HALF_CEILING, '0.499', '50', '5', '0'];
        yield [RoundingMode::HALF_CEILING, '0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_CEILING, '0.000', '0', '0', '0'];
        yield [RoundingMode::HALF_CEILING, '-0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_CEILING, '-0.499', '-50', '-5', '0'];
        yield [RoundingMode::HALF_CEILING, '-0.500', '-50', '-5', '0'];
        yield [RoundingMode::HALF_CEILING, '-0.501', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_CEILING, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_CEILING, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_CEILING, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_CEILING, '-1.499', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_CEILING, '-1.500', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_CEILING, '-1.501', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_CEILING, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_CEILING, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_CEILING, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_CEILING, '-2.499', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_CEILING, '-2.500', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_CEILING, '-2.501', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_CEILING, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_CEILING, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_CEILING, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_CEILING, '-3.499', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_CEILING, '-3.500', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_CEILING, '-3.501', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_FLOOR, '3.501', '350', '35', '4'];
        yield [RoundingMode::HALF_FLOOR, '3.500', '350', '35', '3'];
        yield [RoundingMode::HALF_FLOOR, '3.499', '350', '35', '3'];
        yield [RoundingMode::HALF_FLOOR, '3.001', '300', '30', '3'];
        yield [RoundingMode::HALF_FLOOR, '3.000', '300', '30', '3'];
        yield [RoundingMode::HALF_FLOOR, '2.999', '300', '30', '3'];
        yield [RoundingMode::HALF_FLOOR, '2.501', '250', '25', '3'];
        yield [RoundingMode::HALF_FLOOR, '2.500', '250', '25', '2'];
        yield [RoundingMode::HALF_FLOOR, '2.499', '250', '25', '2'];
        yield [RoundingMode::HALF_FLOOR, '2.001', '200', '20', '2'];
        yield [RoundingMode::HALF_FLOOR, '2.000', '200', '20', '2'];
        yield [RoundingMode::HALF_FLOOR, '1.999', '200', '20', '2'];
        yield [RoundingMode::HALF_FLOOR, '1.501', '150', '15', '2'];
        yield [RoundingMode::HALF_FLOOR, '1.500', '150', '15', '1'];
        yield [RoundingMode::HALF_FLOOR, '1.499', '150', '15', '1'];
        yield [RoundingMode::HALF_FLOOR, '1.001', '100', '10', '1'];
        yield [RoundingMode::HALF_FLOOR, '1.000', '100', '10', '1'];
        yield [RoundingMode::HALF_FLOOR, '0.999', '100', '10', '1'];
        yield [RoundingMode::HALF_FLOOR, '0.501', '50', '5', '1'];
        yield [RoundingMode::HALF_FLOOR, '0.500', '50', '5', '0'];
        yield [RoundingMode::HALF_FLOOR, '0.499', '50', '5', '0'];
        yield [RoundingMode::HALF_FLOOR, '0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_FLOOR, '0.000', '0', '0', '0'];
        yield [RoundingMode::HALF_FLOOR, '-0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_FLOOR, '-0.499', '-50', '-5', '0'];
        yield [RoundingMode::HALF_FLOOR, '-0.500', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_FLOOR, '-0.501', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_FLOOR, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_FLOOR, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_FLOOR, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_FLOOR, '-1.499', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_FLOOR, '-1.500', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_FLOOR, '-1.501', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_FLOOR, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_FLOOR, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_FLOOR, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_FLOOR, '-2.499', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_FLOOR, '-2.500', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_FLOOR, '-2.501', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_FLOOR, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_FLOOR, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_FLOOR, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_FLOOR, '-3.499', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_FLOOR, '-3.500', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_FLOOR, '-3.501', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_EVEN, '3.501', '350', '35', '4'];
        yield [RoundingMode::HALF_EVEN, '3.500', '350', '35', '4'];
        yield [RoundingMode::HALF_EVEN, '3.499', '350', '35', '3'];
        yield [RoundingMode::HALF_EVEN, '3.001', '300', '30', '3'];
        yield [RoundingMode::HALF_EVEN, '3.000', '300', '30', '3'];
        yield [RoundingMode::HALF_EVEN, '2.999', '300', '30', '3'];
        yield [RoundingMode::HALF_EVEN, '2.501', '250', '25', '3'];
        yield [RoundingMode::HALF_EVEN, '2.500', '250', '25', '2'];
        yield [RoundingMode::HALF_EVEN, '2.499', '250', '25', '2'];
        yield [RoundingMode::HALF_EVEN, '2.001', '200', '20', '2'];
        yield [RoundingMode::HALF_EVEN, '2.000', '200', '20', '2'];
        yield [RoundingMode::HALF_EVEN, '1.999', '200', '20', '2'];
        yield [RoundingMode::HALF_EVEN, '1.501', '150', '15', '2'];
        yield [RoundingMode::HALF_EVEN, '1.500', '150', '15', '2'];
        yield [RoundingMode::HALF_EVEN, '1.499', '150', '15', '1'];
        yield [RoundingMode::HALF_EVEN, '1.001', '100', '10', '1'];
        yield [RoundingMode::HALF_EVEN, '1.000', '100', '10', '1'];
        yield [RoundingMode::HALF_EVEN, '0.999', '100', '10', '1'];
        yield [RoundingMode::HALF_EVEN, '0.501', '50', '5', '1'];
        yield [RoundingMode::HALF_EVEN, '0.500', '50', '5', '0'];
        yield [RoundingMode::HALF_EVEN, '0.499', '50', '5', '0'];
        yield [RoundingMode::HALF_EVEN, '0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_EVEN, '0.000', '0', '0', '0'];
        yield [RoundingMode::HALF_EVEN, '-0.001', '0', '0', '0'];
        yield [RoundingMode::HALF_EVEN, '-0.499', '-50', '-5', '0'];
        yield [RoundingMode::HALF_EVEN, '-0.500', '-50', '-5', '0'];
        yield [RoundingMode::HALF_EVEN, '-0.501', '-50', '-5', '-1'];
        yield [RoundingMode::HALF_EVEN, '-0.999', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_EVEN, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_EVEN, '-1.001', '-100', '-10', '-1'];
        yield [RoundingMode::HALF_EVEN, '-1.499', '-150', '-15', '-1'];
        yield [RoundingMode::HALF_EVEN, '-1.500', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_EVEN, '-1.501', '-150', '-15', '-2'];
        yield [RoundingMode::HALF_EVEN, '-1.999', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_EVEN, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_EVEN, '-2.001', '-200', '-20', '-2'];
        yield [RoundingMode::HALF_EVEN, '-2.499', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_EVEN, '-2.500', '-250', '-25', '-2'];
        yield [RoundingMode::HALF_EVEN, '-2.501', '-250', '-25', '-3'];
        yield [RoundingMode::HALF_EVEN, '-2.999', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_EVEN, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_EVEN, '-3.001', '-300', '-30', '-3'];
        yield [RoundingMode::HALF_EVEN, '-3.499', '-350', '-35', '-3'];
        yield [RoundingMode::HALF_EVEN, '-3.500', '-350', '-35', '-4'];
        yield [RoundingMode::HALF_EVEN, '-3.501', '-350', '-35', '-4'];
        yield [RoundingMode::UNNECESSARY, '3.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '3.500', '350', '35', null];
        yield [RoundingMode::UNNECESSARY, '3.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '3.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '3.000', '300', '30', '3'];
        yield [RoundingMode::UNNECESSARY, '2.999', null, null, null];
        yield [RoundingMode::UNNECESSARY, '2.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '2.500', '250', '25', null];
        yield [RoundingMode::UNNECESSARY, '2.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '2.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '2.000', '200', '20', '2'];
        yield [RoundingMode::UNNECESSARY, '1.999', null, null, null];
        yield [RoundingMode::UNNECESSARY, '1.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '1.500', '150', '15', null];
        yield [RoundingMode::UNNECESSARY, '1.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '1.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '1.000', '100', '10', '1'];
        yield [RoundingMode::UNNECESSARY, '0.999', null, null, null];
        yield [RoundingMode::UNNECESSARY, '0.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '0.500', '50', '5', null];
        yield [RoundingMode::UNNECESSARY, '0.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '0.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '0.000', '0', '0', '0'];
        yield [RoundingMode::UNNECESSARY, '-0.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-0.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-0.500', '-50', '-5', null];
        yield [RoundingMode::UNNECESSARY, '-0.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-0.999', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-1.000', '-100', '-10', '-1'];
        yield [RoundingMode::UNNECESSARY, '-1.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-1.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-1.500', '-150', '-15', null];
        yield [RoundingMode::UNNECESSARY, '-1.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-1.999', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-2.000', '-200', '-20', '-2'];
        yield [RoundingMode::UNNECESSARY, '-2.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-2.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-2.500', '-250', '-25', null];
        yield [RoundingMode::UNNECESSARY, '-2.501', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-2.999', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-3.000', '-300', '-30', '-3'];
        yield [RoundingMode::UNNECESSARY, '-3.001', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-3.499', null, null, null];
        yield [RoundingMode::UNNECESSARY, '-3.500', '-350', '-35', null];
        yield [RoundingMode::UNNECESSARY, '-3.501', null, null, null];
    }

    /**
     * @dataProvider providerQuotientAndRemainder
     *
     * @param string $dividend  The dividend.
     * @param string $divisor   The divisor.
     * @param string $quotient  The expected quotient.
     * @param string $remainder The expected remainder.
     */
    public function testQuotientAndRemainder(
        string $dividend,
        string $divisor,
        string $quotient,
        string $remainder
    ): void {
        $dividend = BigDecimal::of($dividend);

        self::assertBigDecimalEquals($quotient, $dividend->quotient($divisor));
        self::assertBigDecimalEquals($remainder, $dividend->remainder($divisor));

        [$q, $r] = $dividend->quotientAndRemainder($divisor);

        self::assertBigDecimalEquals($quotient, $q);
        self::assertBigDecimalEquals($remainder, $r);
    }

    public function providerQuotientAndRemainder(): Iterator
    {
        yield ['1', '123', '0', '1'];
        yield ['1', '-123', '0', '1'];
        yield ['-1', '123', '0', '-1'];
        yield ['-1', '-123', '0', '-1'];
        yield ['1999999999999999999999999', '2000000000000000000000000', '0', '1999999999999999999999999'];
        yield ['1999999999999999999999999', '-2000000000000000000000000', '0', '1999999999999999999999999'];
        yield ['-1999999999999999999999999', '2000000000000000000000000', '0', '-1999999999999999999999999'];
        yield ['-1999999999999999999999999', '-2000000000000000000000000', '0', '-1999999999999999999999999'];
        yield ['123', '1', '123', '0'];
        yield ['123', '-1', '-123', '0'];
        yield ['-123', '1', '-123', '0'];
        yield ['-123', '-1', '123', '0'];
        yield ['123', '2', '61', '1'];
        yield ['123', '-2', '-61', '1'];
        yield ['-123', '2', '-61', '-1'];
        yield ['-123', '-2', '61', '-1'];
        yield ['123', '123', '1', '0'];
        yield ['123', '-123', '-1', '0'];
        yield ['-123', '123', '-1', '0'];
        yield ['-123', '-123', '1', '0'];
        yield ['123', '124', '0', '123'];
        yield ['123', '-124', '0', '123'];
        yield ['-123', '124', '0', '-123'];
        yield ['-123', '-124', '0', '-123'];
        yield ['124', '123', '1', '1'];
        yield ['124', '-123', '-1', '1'];
        yield ['-124', '123', '-1', '-1'];
        yield ['-124', '-123', '1', '-1'];
        yield ['1000000000000000000000000000000', '3', '333333333333333333333333333333', '1'];
        yield ['1000000000000000000000000000000', '9', '111111111111111111111111111111', '1'];
        yield ['1000000000000000000000000000000', '11', '90909090909090909090909090909', '1'];
        yield ['1000000000000000000000000000000', '13', '76923076923076923076923076923', '1'];
        yield ['1000000000000000000000000000000', '21', '47619047619047619047619047619', '1'];
        yield ['123456789123456789123456789', '987654321987654321', '124999998', '850308642973765431'];
        yield ['123456789123456789123456789', '-87654321987654321', '-1408450676', '65623397056685793'];
        yield ['-123456789123456789123456789', '7654321987654321', '-16129030020', '-1834176331740369'];
        yield ['-123456789123456789123456789', '-654321987654321', '188678955396', '-205094497790673'];
        yield ['10.11', '3.3', '3', '0.21'];
        yield ['1', '-0.0013', '-769', '0.0003'];
        yield ['-1.000000000000000000001', '0.0000009298439898981609', '-1075449', '-0.0000002109080127582569'];
        yield [
            '-1278438782896060000132323.32333',
            '-53.4836775545640521556878910541',
            '23903344746475158719036',
            '-30.0786684482104867175202241524',
        ];
        yield [
            '23999593472872987498347103908209387429846376',
            '-0.005',
            '-4799918694574597499669420781641877485969275200',
            '0.000',
        ];
        yield ['1000000000000000000000000000000.0', '3', '333333333333333333333333333333', '1.0'];
        yield ['1000000000000000000000000000000.0', '9', '111111111111111111111111111111', '1.0'];
        yield ['1000000000000000000000000000000.0', '11', '90909090909090909090909090909', '1.0'];
        yield ['1000000000000000000000000000000.0', '13', '76923076923076923076923076923', '1.0'];
        yield ['0.9999999999999999999999999999999', '0.21', '4', '0.1599999999999999999999999999999'];
        yield ['1000000000000000000000000000000.0', '3.9', '256410256410256410256410256410', '1.0'];
        yield ['-1000000000000000000000000000000.0', '9.8', '-102040816326530612244897959183', '-6.6'];
        yield ['1000000000000000000000000000000.0', '-11.7', '-85470085470085470085470085470', '1.0'];
        yield ['-1000000000000000000000000000000.0', '-13.7', '72992700729927007299270072992', '-9.6'];
        yield ['0.99999999999999999999999999999999', '0.215', '4', '0.13999999999999999999999999999999'];
    }

    public function testQuotientOfZeroThrowsException(): void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1.2)->quotient(0);
    }

    public function testRemainderOfZeroThrowsException(): void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1.2)->remainder(0);
    }

    public function testQuotientAndRemainderOfZeroThrowsException(): void
    {
        $this->expectException(DivisionByZeroException::class);
        BigDecimal::of(1.2)->quotientAndRemainder(0);
    }

    /**
     * @dataProvider providerSqrt
     */
    public function testSqrt(string $number, int $scale, string $sqrt): void
    {
        $number = BigDecimal::of($number);

        self::assertBigDecimalEquals($sqrt, $number->sqrt($scale));
    }

    public function providerSqrt(): Iterator
    {
        yield ['0', 0, '0'];
        yield ['0', 1, '0.0'];
        yield ['0', 2, '0.00'];
        yield ['0.9', 0, '0'];
        yield ['0.9', 1, '0.9'];
        yield ['0.9', 2, '0.94'];
        yield ['0.9', 20, '0.94868329805051379959'];
        yield ['1', 0, '1'];
        yield ['1', 1, '1.0'];
        yield ['1', 2, '1.00'];
        yield ['1.01', 0, '1'];
        yield ['1.01', 1, '1.0'];
        yield ['1.01', 2, '1.00'];
        yield ['1.01', 50, '1.00498756211208902702192649127595761869450234700263'];
        yield ['2', 0, '1'];
        yield ['2', 1, '1.4'];
        yield ['2', 2, '1.41'];
        yield ['2', 3, '1.414'];
        yield ['2.0', 10, '1.4142135623'];
        yield [
            '2.00',
            100,
            '1.4142135623730950488016887242096980785696718753769480731766797379907324784621070388503875343276415727',
        ];
        yield [
            '2.01',
            100,
            '1.4177446878757825202955618542708577926112284524295925478183838620667251915680282359142910339946198902',
        ];
        yield ['3', 0, '1'];
        yield ['3', 1, '1.7'];
        yield ['3', 2, '1.73'];
        yield ['3.0', 3, '1.732'];
        yield [
            '3.00',
            100,
            '1.7320508075688772935274463415058723669428052538103806280558069794519330169088000370811461867572485756',
        ];
        yield [
            '3.01',
            100,
            '1.7349351572897472412324994276999816954904345949805056180301062018688462654791174593725963060697252989',
        ];
        yield ['4', 0, '2'];
        yield ['4.0', 1, '2.0'];
        yield ['4.00', 2, '2.00'];
        yield ['4.000', 50, '2.00000000000000000000000000000000000000000000000000'];
        yield ['4.001', 50, '2.00024998437695281987761450010498155779765165614814'];
        yield ['8', 0, '2'];
        yield ['8', 1, '2.8'];
        yield ['8', 2, '2.82'];
        yield ['8', 3, '2.828'];
        yield [
            '8',
            100,
            '2.8284271247461900976033774484193961571393437507538961463533594759814649569242140777007750686552831454',
        ];
        yield ['9', 0, '3'];
        yield ['9', 1, '3.0'];
        yield ['9', 2, '3.00'];
        yield ['9.0', 3, '3.000'];
        yield ['9.00', 50, '3.00000000000000000000000000000000000000000000000000'];
        yield [
            '9.000000000001',
            100,
            '3.0000000000001666666666666620370370370372942386831275541552354823973654295585021450670206100119695201',
        ];
        yield ['15', 0, '3'];
        yield ['15', 1, '3.8'];
        yield ['15', 2, '3.87'];
        yield ['15', 3, '3.872'];
        yield [
            '15',
            100,
            '3.8729833462074168851792653997823996108329217052915908265875737661134830919369790335192873768586735179',
        ];
        yield ['16', 0, '4'];
        yield ['16', 1, '4.0'];
        yield ['16.0', 2, '4.00'];
        yield ['16.0', 50, '4.00000000000000000000000000000000000000000000000000'];
        yield [
            '16.9',
            100,
            '4.1109609582188931315985616077625340938354216811227818749147563086303727702310096877475225408930903837',
        ];
        yield ['24.000000', 0, '4'];
        yield ['24.000000', 1, '4.8'];
        yield [
            '24.000000',
            100,
            '4.8989794855663561963945681494117827839318949613133402568653851345019207549146300530797188662092804696',
        ];
        yield ['25.0', 0, '5'];
        yield ['25.0', 1, '5.0'];
        yield ['25.0', 2, '5.00'];
        yield ['25.0', 50, '5.00000000000000000000000000000000000000000000000000'];
        yield ['35.0', 0, '5'];
        yield ['35.0', 1, '5.9'];
        yield ['35.0', 2, '5.91'];
        yield ['35.0', 3, '5.916'];
        yield ['35.0', 4, '5.9160'];
        yield ['35.0', 5, '5.91607'];
        yield [
            '35.0',
            100,
            '5.9160797830996160425673282915616170484155012307943403228797196691428224591056530367657525271831091780',
        ];
        yield [
            '35.000000000000001',
            100,
            '5.9160797830996161270827537644132741956957234470198942745396537100863774127246283998019188486148209315',
        ];
        yield [
            '35.999999999999999999999999',
            100,
            '5.9999999999999999999999999166666666666666666666666660879629629629629629629629549254115226337448559670',
        ];
        yield ['36.00', 0, '6'];
        yield ['36.00', 1, '6.0'];
        yield ['36.00', 2, '6.00'];
        yield ['36.00', 3, '6.000'];
        yield ['36.00', 50, '6.00000000000000000000000000000000000000000000000000'];
        yield ['48.00', 0, '6'];
        yield ['48.00', 2, '6.92'];
        yield ['48.00', 10, '6.9282032302'];
        yield [
            '48.00',
            100,
            '6.9282032302755091741097853660234894677712210152415225122232279178077320676352001483245847470289943027',
        ];
        yield [
            '48.99',
            100,
            '6.9992856778388464346356995151906110076016504604210370025102717611026824990288822856842902895079113686',
        ];
        yield ['49.000', 0, '7'];
        yield ['49.000', 1, '7.0'];
        yield ['49.000', 2, '7.00'];
        yield ['49.000', 50, '7.00000000000000000000000000000000000000000000000000'];
        yield ['63.000', 0, '7'];
        yield ['63.000', 1, '7.9'];
        yield ['63.000', 50, '7.93725393319377177150484726091778127713077754924735'];
        yield [
            '63.000',
            100,
            '7.9372539331937717715048472609177812771307775492473505411050033776032064696908508832811786594236308318',
        ];
        yield [
            '63.999',
            100,
            '7.9999374997558574676327405322784897796491608172719005229581557200716046333750586163480165729931946120',
        ];
        yield ['64.000', 0, '8'];
        yield ['64.000', 1, '8.0'];
        yield ['64.000', 2, '8.00'];
        yield ['64.000', 3, '8.000'];
        yield ['64.000', 5, '8.00000'];
        yield ['64.000', 10, '8.0000000000'];
        yield [
            '64.001',
            100,
            '8.0000624997558612823300065647321162325407871131077227756517693705917932138407362275702583154308502098',
        ];
        yield ['80.0000', 0, '8'];
        yield ['80.0000', 1, '8.9'];
        yield ['80.0000', 2, '8.94'];
        yield ['80.0000', 3, '8.944'];
        yield [
            '80.0000',
            100,
            '8.9442719099991587856366946749251049417624734384461028970835889816420837025512195976576576335151290998',
        ];
        yield [
            '80.9999',
            100,
            '8.9999944444427297657453970731875168384468609868424736666730189165386046474591090720552569708711327582',
        ];
        yield ['81.0000', 0, '9'];
        yield ['81.0000', 1, '9.0'];
        yield ['81.0000', 2, '9.00'];
        yield ['81.0000', 3, '9.000'];
        yield [
            '81.0000',
            100,
            '9.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        ];
        yield [
            '81.0001',
            100,
            '9.0000055555538408789733941603538253684017661110877201067416238610513773808272313229667905649560045446',
        ];
        yield ['99.0000', 0, '9'];
        yield ['99.0000', 1, '9.9'];
        yield ['99.0000', 2, '9.94'];
        yield ['99.0000', 3, '9.949'];
        yield [
            '99.0000',
            100,
            '9.9498743710661995473447982100120600517812656367680607911760464383494539278271315401265301973848719527',
        ];
        yield [
            '99.9999',
            100,
            '9.9999949999987499993749996093747265622949217138670565794807433154677543830170797681772155388691566433',
        ];
        yield ['100.00000', 0, '10'];
        yield ['100.00000', 1, '10.0'];
        yield ['100.00000', 2, '10.00'];
        yield ['100.00000', 3, '10.000'];
        yield [
            '100.00000',
            100,
            '10.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        ];
        yield [
            '100.00001',
            100,
            '10.0000004999999875000006249999609375027343747949218911132799407960075378325233467481612458396019939178',
        ];
        yield [
            '536137214136734800142146901786039940282473271927911507640625',
            100,
            '732213912826528310663262741625.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        ];
        yield [
            '536137214136734800142146901787504368108126328549238033123875',
            100,
            '732213912826528310663262741625.9999999999999999999999999999993171394434860226777473099041602996062918768042176806905944729779944325',
        ];
        yield [
            '536137214136734800142146901787504368108126328549238033123876',
            100,
            '732213912826528310663262741626.0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        ];
        yield [
            '5651495859544574019979802175954184725583245698990648064256.0000000001',
            100,
            '75176431543034642899535752016.0000000000000000000000000000000000000006651020668808623891656648072197795077909627885735661691784990',
        ];
        yield [
            '5651495859544574019979802176104537588669314984789719568288.9999999999',
            100,
            '75176431543034642899535752016.9999999999999999999999999999999999999993348979331191376108343351927890677073964211143577833889761307',
        ];
        yield [
            '5651495859544574019979802176104537588669314984789719568289.00000000001',
            100,
            '75176431543034642899535752017.0000000000000000000000000000000000000000665102066880862389165664807210932292603578885642216611023869',
        ];
        yield ['17', 60, '4.123105625617660549821409855974077025147199225373620434398633'];
        yield ['17', 61, '4.1231056256176605498214098559740770251471992253736204343986335'];
        yield ['17', 62, '4.12310562561766054982140985597407702514719922537362043439863357'];
        yield ['17', 63, '4.123105625617660549821409855974077025147199225373620434398633573'];
        yield ['17', 64, '4.1231056256176605498214098559740770251471992253736204343986335730'];
        yield ['17', 65, '4.12310562561766054982140985597407702514719922537362043439863357309'];
        yield ['17', 66, '4.123105625617660549821409855974077025147199225373620434398633573094'];
        yield ['17', 67, '4.1231056256176605498214098559740770251471992253736204343986335730949'];
        yield ['17', 68, '4.12310562561766054982140985597407702514719922537362043439863357309495'];
        yield ['17', 69, '4.123105625617660549821409855974077025147199225373620434398633573094954'];
        yield ['17', 70, '4.1231056256176605498214098559740770251471992253736204343986335730949543'];
        yield ['0.0019', 0, '0'];
        yield ['0.0019', 1, '0.0'];
        yield ['0.0019', 2, '0.04'];
        yield ['0.0019', 3, '0.043'];
        yield ['0.0019', 10, '0.0435889894'];
        yield ['0.0019', 70, '0.0435889894354067355223698198385961565913700392523244493689034413815955'];
        yield ['0.00000000015727468406479', 0, '0'];
        yield ['0.00000000015727468406479', 1, '0.0'];
        yield ['0.00000000015727468406479', 2, '0.00'];
        yield ['0.00000000015727468406479', 3, '0.000'];
        yield ['0.00000000015727468406479', 4, '0.0000'];
        yield ['0.00000000015727468406479', 5, '0.00001'];
        yield ['0.00000000015727468406479', 6, '0.000012'];
        yield ['0.00000000015727468406479', 7, '0.0000125'];
        yield ['0.00000000015727468406479', 8, '0.00001254'];
        yield ['0.00000000015727468406479', 9, '0.000012540'];
        yield ['0.00000000015727468406479', 10, '0.0000125409'];
        yield [
            '0.00000000015727468406479',
            100,
            '0.0000125409203834802332262270521125445995500262491027973910117525063503841909945796984522050136239469',
        ];
        yield ['0.04', 0, '0'];
        yield ['0.04', 1, '0.2'];
        yield ['0.04', 2, '0.20'];
        yield ['0.04', 10, '0.2000000000'];
        yield ['0.0004', 4, '0.0200'];
        yield ['0.00000000000000000000000000000004', 8, '0.00000000'];
        yield ['0.00000000000000000000000000000004', 16, '0.0000000000000002'];
        yield ['0.00000000000000000000000000000004', 32, '0.00000000000000020000000000000000'];
        yield ['0.000000000000000000000000000000004', 32, '0.00000000000000006324555320336758'];
        yield [
            '111111111111111111111.11111111111111',
            90,
            '10540925533.894597773329645148109061726360556128277733889543457102096672435043305908711407747018689086',
        ];
    }

    public function testSqrtOfNegativeNumber(): void
    {
        $number = BigDecimal::of(-1);
        $this->expectException(NegativeNumberException::class);
        $number->sqrt(0);
    }

    public function testSqrtWithNegativeScale(): void
    {
        $number = BigDecimal::of(1);
        $this->expectException(InvalidArgumentException::class);
        $number->sqrt(-1);
    }

    /**
     * @dataProvider providerPower
     *
     * @param string  $number        The base number.
     * @param int     $exponent      The exponent to apply.
     * @param string  $unscaledValue The expected unscaled value of the result.
     * @param int     $scale         The expected scale of the result.
     */
    public function testPower(string $number, int $exponent, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->power($exponent));
    }

    public function providerPower(): Iterator
    {
        yield ['-3', 0, '1', 0];
        yield ['-2', 0, '1', 0];
        yield ['-1', 0, '1', 0];
        yield ['0', 0, '1', 0];
        yield ['1', 0, '1', 0];
        yield ['2', 0, '1', 0];
        yield ['3', 0, '1', 0];
        yield ['-3', 1, '-3', 0];
        yield ['-2', 1, '-2', 0];
        yield ['-1', 1, '-1', 0];
        yield ['0', 1, '0', 0];
        yield ['1', 1, '1', 0];
        yield ['2', 1, '2', 0];
        yield ['3', 1, '3', 0];
        yield ['-3', 2, '9', 0];
        yield ['-2', 2, '4', 0];
        yield ['-1', 2, '1', 0];
        yield ['0', 2, '0', 0];
        yield ['1', 2, '1', 0];
        yield ['2', 2, '4', 0];
        yield ['3', 2, '9', 0];
        yield ['-3', 3, '-27', 0];
        yield ['-2', 3, '-8', 0];
        yield ['-1', 3, '-1', 0];
        yield ['0', 3, '0', 0];
        yield ['1', 3, '1', 0];
        yield ['2', 3, '8', 0];
        yield ['3', 3, '27', 0];
        yield ['0', 1_000_000, '0', 0];
        yield ['1', 1_000_000, '1', 0];
        yield ['-2', 255, '-57896044618658097711785492504343953926634992332820282019728792003956564819968', 0];
        yield ['2', 256, '115792089237316195423570985008687907853269984665640564039457584007913129639936', 0];
        yield ['-1.23', 0, '1', 0];
        yield ['-1.23', 0, '1', 0];
        yield ['-1.23', 33, '-926549609804623448265268294182900512918058893428212027689876489708283', 66];
        yield ['1.23', 34, '113965602005968684136628000184496763088921243891670079405854808234118809', 68];
        yield ['-123456789', 8, '53965948844821664748141453212125737955899777414752273389058576481', 0];
        yield ['9876543210', 7, '9167159269868350921847491739460569765344716959834325922131706410000000', 0];
    }

    /**
     * @dataProvider providerPowerWithInvalidExponentThrowsException
     */
    public function testPowerWithInvalidExponentThrowsException(int $power): void
    {
        $this->expectException(InvalidArgumentException::class);
        BigDecimal::of(1)->power($power);
    }

    public function providerPowerWithInvalidExponentThrowsException(): Iterator
    {
        yield [-1];
        yield [1_000_001];
    }

    /**
     * @dataProvider toScaleProvider
     *
     * @param string $number        The number to scale.
     * @param int    $toScale       The scale to apply.
     * @param int    $roundingMode  The rounding mode to apply.
     * @param string $unscaledValue The expected unscaled value of the result.
     * @param int    $scale         The expected scale of the result.
     */
    public function testToScale(
        string $number,
        int $toScale,
        int $roundingMode,
        string $unscaledValue,
        int $scale
    ): void {
        $decimal = BigDecimal::of($number)->toScale($toScale, $roundingMode);
        self::assertBigDecimalInternalValues($unscaledValue, $scale, $decimal);
    }

    public function toScaleProvider(): array
    {
        return [
            ['123.45', 0, RoundingMode::DOWN, '123', 0],
            ['123.45', 1, RoundingMode::UP, '1235', 1],
            ['123.45', 2, RoundingMode::UNNECESSARY, '12345', 2],
            ['123.45', 5, RoundingMode::UNNECESSARY, '12345000', 5],
        ];
    }

    /**
     * @dataProvider providerWithPointMovedLeft
     *
     * @param string $number   The decimal number as a string.
     * @param int    $places   The number of decimal places to move left.
     * @param string $expected The expected result.
     */
    public function testWithPointMovedLeft(string $number, int $places, string $expected): void
    {
        self::assertBigDecimalEquals($expected, BigDecimal::of($number)->withPointMovedLeft($places));
    }

    public function providerWithPointMovedLeft(): Iterator
    {
        yield ['0', -2, '0'];
        yield ['0', -1, '0'];
        yield ['0', 0, '0'];
        yield ['0', 1, '0.0'];
        yield ['0', 2, '0.00'];
        yield ['0.0', -2, '0'];
        yield ['0.0', -1, '0'];
        yield ['0.0', 0, '0.0'];
        yield ['0.0', 1, '0.00'];
        yield ['0.0', 2, '0.000'];
        yield ['1', -2, '100'];
        yield ['1', -1, '10'];
        yield ['1', 0, '1'];
        yield ['1', 1, '0.1'];
        yield ['1', 2, '0.01'];
        yield ['12', -2, '1200'];
        yield ['12', -1, '120'];
        yield ['12', 0, '12'];
        yield ['12', 1, '1.2'];
        yield ['12', 2, '0.12'];
        yield ['1.1', -2, '110'];
        yield ['1.1', -1, '11'];
        yield ['1.1', 0, '1.1'];
        yield ['1.1', 1, '0.11'];
        yield ['1.1', 2, '0.011'];
        yield ['0.1', -2, '10'];
        yield ['0.1', -1, '1'];
        yield ['0.1', 0, '0.1'];
        yield ['0.1', 1, '0.01'];
        yield ['0.1', 2, '0.001'];
        yield ['0.01', -2, '1'];
        yield ['0.01', -1, '0.1'];
        yield ['0.01', 0, '0.01'];
        yield ['0.01', 1, '0.001'];
        yield ['0.01', 2, '0.0001'];
        yield ['-9', -2, '-900'];
        yield ['-9', -1, '-90'];
        yield ['-9', 0, '-9'];
        yield ['-9', 1, '-0.9'];
        yield ['-9', 2, '-0.09'];
        yield ['-0.9', -2, '-90'];
        yield ['-0.9', -1, '-9'];
        yield ['-0.9', 0, '-0.9'];
        yield ['-0.9', 1, '-0.09'];
        yield ['-0.9', 2, '-0.009'];
        yield ['-0.09', -2, '-9'];
        yield ['-0.09', -1, '-0.9'];
        yield ['-0.09', 0, '-0.09'];
        yield ['-0.09', 1, '-0.009'];
        yield ['-0.09', 2, '-0.0009'];
        yield ['-12.3', -2, '-1230'];
        yield ['-12.3', -1, '-123'];
        yield ['-12.3', 0, '-12.3'];
        yield ['-12.3', 1, '-1.23'];
        yield ['-12.3', 2, '-0.123'];
    }

    /**
     * @dataProvider providerWithPointMovedRight
     *
     * @param string $number   The decimal number as a string.
     * @param int    $places   The number of decimal places to move right.
     * @param string $expected The expected result.
     */
    public function testWithPointMovedRight(string $number, int $places, string $expected): void
    {
        self::assertBigDecimalEquals($expected, BigDecimal::of($number)->withPointMovedRight($places));
    }

    public function providerWithPointMovedRight(): Iterator
    {
        yield ['0', -2, '0.00'];
        yield ['0', -1, '0.0'];
        yield ['0', 0, '0'];
        yield ['0', 1, '0'];
        yield ['0', 2, '0'];
        yield ['0.0', -2, '0.000'];
        yield ['0.0', -1, '0.00'];
        yield ['0.0', 0, '0.0'];
        yield ['0.0', 1, '0'];
        yield ['0.0', 2, '0'];
        yield ['9', -2, '0.09'];
        yield ['9', -1, '0.9'];
        yield ['9', 0, '9'];
        yield ['9', 1, '90'];
        yield ['9', 2, '900'];
        yield ['89', -2, '0.89'];
        yield ['89', -1, '8.9'];
        yield ['89', 0, '89'];
        yield ['89', 1, '890'];
        yield ['89', 2, '8900'];
        yield ['8.9', -2, '0.089'];
        yield ['8.9', -1, '0.89'];
        yield ['8.9', 0, '8.9'];
        yield ['8.9', 1, '89'];
        yield ['8.9', 2, '890'];
        yield ['0.9', -2, '0.009'];
        yield ['0.9', -1, '0.09'];
        yield ['0.9', 0, '0.9'];
        yield ['0.9', 1, '9'];
        yield ['0.9', 2, '90'];
        yield ['0.09', -2, '0.0009'];
        yield ['0.09', -1, '0.009'];
        yield ['0.09', 0, '0.09'];
        yield ['0.09', 1, '0.9'];
        yield ['0.09', 2, '9'];
        yield ['-1', -2, '-0.01'];
        yield ['-1', -1, '-0.1'];
        yield ['-1', 0, '-1'];
        yield ['-1', 1, '-10'];
        yield ['-1', 2, '-100'];
        yield ['-0.1', -2, '-0.001'];
        yield ['-0.1', -1, '-0.01'];
        yield ['-0.1', 0, '-0.1'];
        yield ['-0.1', 1, '-1'];
        yield ['-0.1', 2, '-10'];
        yield ['-0.01', -2, '-0.0001'];
        yield ['-0.01', -1, '-0.001'];
        yield ['-0.01', 0, '-0.01'];
        yield ['-0.01', 1, '-0.1'];
        yield ['-0.01', 2, '-1'];
        yield ['-12.3', -2, '-0.123'];
        yield ['-12.3', -1, '-1.23'];
        yield ['-12.3', 0, '-12.3'];
        yield ['-12.3', 1, '-123'];
        yield ['-12.3', 2, '-1230'];
    }

    /**
     * @dataProvider providerStripTrailingZeros
     *
     * @param string $number   The number to trim.
     * @param string $expected The expected result.
     */
    public function testStripTrailingZeros(string $number, string $expected): void
    {
        self::assertBigDecimalEquals($expected, BigDecimal::of($number)->stripTrailingZeros());
    }

    public function providerStripTrailingZeros(): Iterator
    {
        yield ['0', '0'];
        yield ['0.0', '0'];
        yield ['0.00', '0'];
        yield ['0.000', '0'];
        yield ['0.1', '0.1'];
        yield ['0.01', '0.01'];
        yield ['0.001', '0.001'];
        yield ['0.100', '0.1'];
        yield ['0.0100', '0.01'];
        yield ['0.00100', '0.001'];
        yield ['1', '1'];
        yield ['1.0', '1'];
        yield ['1.00', '1'];
        yield ['1.10', '1.1'];
        yield ['1.123000', '1.123'];
        yield ['10', '10'];
        yield ['10.0', '10'];
        yield ['10.00', '10'];
        yield ['10.10', '10.1'];
        yield ['10.01', '10.01'];
        yield ['10.010', '10.01'];
        yield ['100', '100'];
        yield ['100.0', '100'];
        yield ['100.00', '100'];
        yield ['100.01', '100.01'];
        yield ['100.10', '100.1'];
        yield ['100.010', '100.01'];
        yield ['100.100', '100.1'];
    }

    /**
     * @dataProvider providerAbs
     *
     * @param string $number        The number as a string.
     * @param string $unscaledValue The expected unscaled value of the absolute result.
     * @param int    $scale         The expected scale of the absolute result.
     */
    public function testAbs(string $number, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->abs());
    }

    public function providerAbs(): Iterator
    {
        yield ['123', '123', 0];
        yield ['-123', '123', 0];
        yield ['123.456', '123456', 3];
        yield ['-123.456', '123456', 3];
    }

    /**
     * @dataProvider providerNegated
     *
     * @param string $number        The number to negate as a string.
     * @param string $unscaledValue The expected unscaled value of the result.
     * @param int    $scale         The expected scale of the result.
     */
    public function testNegated(string $number, string $unscaledValue, int $scale): void
    {
        self::assertBigDecimalInternalValues($unscaledValue, $scale, BigDecimal::of($number)->negated());
    }

    public function providerNegated(): Iterator
    {
        yield ['123', '-123', 0];
        yield ['-123', '123', 0];
        yield ['123.456', '-123456', 3];
        yield ['-123.456', '123456', 3];
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string           $a The base number as a string.
     * @param string|int|float $b The number to compare to.
     * @param int              $c The comparison result.
     */
    public function testCompareTo(string $a, $b, int $c): void
    {
        self::assertSame($c, BigDecimal::of($a)->compareTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string           $a The base number as a string.
     * @param string|int|float $b The number to compare to.
     * @param int              $c The comparison result.
     */
    public function testIsEqualTo(string $a, $b, int $c): void
    {
        self::assertSame($c === 0, BigDecimal::of($a)->isEqualTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string           $a The base number as a string.
     * @param string|int|float $b The number to compare to.
     * @param int              $c The comparison result.
     */
    public function testIsLessThan(string $a, $b, int $c): void
    {
        self::assertSame($c < 0, BigDecimal::of($a)->isLessThan($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string           $a The base number as a string.
     * @param string|int|float $b The number to compare to.
     * @param int              $c The comparison result.
     */
    public function testIsLessThanOrEqualTo(string $a, $b, int $c): void
    {
        self::assertSame($c <= 0, BigDecimal::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string           $a The base number as a string.
     * @param string|int|float $b The number to compare to.
     * @param int              $c The comparison result.
     */
    public function testIsGreaterThan(string $a, $b, int $c): void
    {
        self::assertSame($c > 0, BigDecimal::of($a)->isGreaterThan($b));
    }

    /**
     * @dataProvider providerCompareTo
     *
     * @param string           $a The base number as a string.
     * @param string|int|float $b The number to compare to.
     * @param int              $c The comparison result.
     */
    public function testIsGreaterThanOrEqualTo(string $a, $b, int $c): void
    {
        self::assertSame($c >= 0, BigDecimal::of($a)->isGreaterThanOrEqualTo($b));
    }

    public function providerCompareTo(): Iterator
    {
        yield ['123', '123', 0];
        yield ['123', '456', -1];
        yield ['456', '123', 1];
        yield ['456', '456', 0];
        yield ['-123', '-123', 0];
        yield ['-123', '456', -1];
        yield ['456', '-123', 1];
        yield ['456', '456', 0];
        yield ['123', '123', 0];
        yield ['123', '-456', 1];
        yield ['-456', '123', -1];
        yield ['-456', '456', -1];
        yield ['-123', '-123', 0];
        yield ['-123', '-456', 1];
        yield ['-456', '-123', -1];
        yield ['-456', '-456', 0];
        yield ['123.000000000000000000000000000000000000000000000', '123', 0];
        yield ['123.000000000000000000000000000000000000000000001', '123', 1];
        yield ['122.999999999999999999999999999999999999999999999', '123', -1];
        yield ['123.0', '123.000000000000000000000000000000000000000000000', 0];
        yield ['123.0', '123.000000000000000000000000000000000000000000001', -1];
        yield ['123.0', '122.999999999999999999999999999999999999999999999', 1];
        yield ['-0.000000000000000000000000000000000000000000000000001', '0', -1];
        yield ['0.000000000000000000000000000000000000000000000000001', '0', 1];
        yield ['0.000000000000000000000000000000000000000000000000000', '0', 0];
        yield ['0', '-0.000000000000000000000000000000000000000000000000001', 1];
        yield ['0', '0.000000000000000000000000000000000000000000000000001', -1];
        yield ['0', '0.000000000000000000000000000000000000000000000000000', 0];
        yield ['123.9999999999999999999999999999999999999', 124, -1];
        yield ['124.0000000000000000000000000000000000000', '124', 0];
        yield ['124.0000000000000000000000000000000000001', 124.0, 1];
        yield ['123.9999999999999999999999999999999999999', '1508517100733469660019804/12165460489786045645321', -1];
        yield ['124.0000000000000000000000000000000000000', '1508517100733469660019804/12165460489786045645321', 0];
        yield ['124.0000000000000000000000000000000000001', '1508517100733469660019804/12165460489786045645321', 1];
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testGetSign($number, int $sign): void
    {
        self::assertSame($sign, BigDecimal::of($number)->getSign());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsZero($number, int $sign): void
    {
        self::assertSame($sign === 0, BigDecimal::of($number)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsNegative($number, int $sign): void
    {
        self::assertSame($sign < 0, BigDecimal::of($number)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsNegativeOrZero($number, int $sign): void
    {
        self::assertSame($sign <= 0, BigDecimal::of($number)->isNegativeOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsPositive($number, int $sign): void
    {
        self::assertSame($sign > 0, BigDecimal::of($number)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param number|string $number The number to test.
     * @param int           $sign   The sign of the number.
     */
    public function testIsPositiveOrZero($number, int $sign): void
    {
        self::assertSame($sign >= 0, BigDecimal::of($number)->isPositiveOrZero());
    }

    public function providerSign(): Iterator
    {
        yield [0, 0];
        yield [-0, 0];
        yield [1, 1];
        yield [-1, -1];
        yield [PHP_INT_MAX, 1];
        yield [PHP_INT_MIN, -1];
        yield [1.0, 1];
        yield [-1.0, -1];
        yield [0.1, 1];
        yield [-0.1, -1];
        yield [0.0, 0];
        yield [-0.0, 0];
        yield ['1.00', 1];
        yield ['-1.00', -1];
        yield ['0.10', 1];
        yield ['-0.10', -1];
        yield ['0.01', 1];
        yield ['-0.01', -1];
        yield ['0.00', 0];
        yield ['-0.00', 0];
        yield ['0.000000000000000000000000000000000000000000000000000000000000000000000000000001', 1];
        yield ['0.000000000000000000000000000000000000000000000000000000000000000000000000000000', 0];
        yield ['-0.000000000000000000000000000000000000000000000000000000000000000000000000000001', -1];
    }

    /**
     * @dataProvider providerGetIntegralPart
     *
     * @param string $number   The number to test.
     * @param string $expected The expected integral value.
     */
    public function testGetIntegralPart(string $number, string $expected): void
    {
        self::assertSame($expected, BigDecimal::of($number)->getIntegralPart());
    }

    public function providerGetIntegralPart(): Iterator
    {
        yield ['1.23', '1'];
        yield ['-1.23', '-1'];
        yield ['0.123', '0'];
        yield ['0.001', '0'];
        yield ['123.0', '123'];
        yield ['12', '12'];
        yield ['1234.5678', '1234'];
    }

    /**
     * @dataProvider providerGetFractionalPart
     *
     * @param string $number   The number to test.
     * @param string $expected The expected fractional value.
     */
    public function testGetFractionalPart(string $number, string $expected): void
    {
        self::assertSame($expected, BigDecimal::of($number)->getFractionalPart());
    }

    public function providerGetFractionalPart(): Iterator
    {
        yield ['1.23', '23'];
        yield ['-1.23', '23'];
        yield ['1', ''];
        yield ['-1', ''];
        yield ['0', ''];
        yield ['0.001', '001'];
    }

    /**
     * @dataProvider providerHasNonZeroFractionalPart
     *
     * @param string $number                   The number to test.
     * @param bool   $hasNonZeroFractionalPart The expected return value.
     */
    public function testHasNonZeroFractionalPart(string $number, bool $hasNonZeroFractionalPart): void
    {
        self::assertSame($hasNonZeroFractionalPart, BigDecimal::of($number)->hasNonZeroFractionalPart());
    }

    public function providerHasNonZeroFractionalPart(): Iterator
    {
        yield ['1', false];
        yield ['1.0', false];
        yield ['1.01', true];
        yield ['-123456789', false];
        yield ['-123456789.0000000000000000000000000000000000000000000000000000000', false];
        yield ['-123456789.00000000000000000000000000000000000000000000000000000001', true];
    }

    /**
     * @dataProvider providerToBigInteger
     *
     * @param string $decimal  The number to convert.
     * @param string $expected The expected value.
     */
    public function testToBigInteger(string $decimal, string $expected): void
    {
        self::assertBigIntegerEquals($expected, BigDecimal::of($decimal)->toBigInteger());
    }

    public function providerToBigInteger(): Iterator
    {
        yield ['0', '0'];
        yield ['1', '1'];
        yield ['0.0', '0'];
        yield ['1.0', '1'];
        yield [
            '-45646540654984984654165151654557478978940.0000000000000',
            '-45646540654984984654165151654557478978940',
        ];
    }

    /**
     * @dataProvider providerToBigIntegerThrowsExceptionWhenRoundingNecessary
     *
     * @param string $decimal A decimal number with a non-zero fractional part.
     */
    public function testToBigIntegerThrowsExceptionWhenRoundingNecessary(string $decimal): void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigDecimal::of($decimal)->toBigInteger();
    }

    public function providerToBigIntegerThrowsExceptionWhenRoundingNecessary(): Iterator
    {
        yield ['0.1'];
        yield ['-0.1'];
        yield ['0.01'];
        yield ['-0.01'];
        yield ['1.002'];
        yield ['0.001'];
        yield ['-1.002'];
        yield ['-0.001'];
        yield ['-45646540654984984654165151654557478978940.0000000000001'];
    }

    /**
     * @dataProvider providerToBigRational
     *
     * @param string $decimal  The decimal number to test.
     * @param string $rational The expected rational number.
     */
    public function testToBigRational(string $decimal, string $rational): void
    {
        self::assertBigRationalEquals($rational, BigDecimal::of($decimal)->toBigRational());
    }

    public function providerToBigRational(): Iterator
    {
        yield ['0', '0'];
        yield ['1', '1'];
        yield ['-1', '-1'];
        yield ['0.0', '0/10'];
        yield ['1.0', '10/10'];
        yield ['-1.0', '-10/10'];
        yield ['0.00', '0/100'];
        yield ['1.00', '100/100'];
        yield ['-1.00', '-100/100'];
        yield ['0.9', '9/10'];
        yield ['0.90', '90/100'];
        yield ['0.900', '900/1000'];
        yield ['0.10', '10/100'];
        yield ['0.11', '11/100'];
        yield ['0.99', '99/100'];
        yield ['0.990', '990/1000'];
        yield ['0.9900', '9900/10000'];
        yield ['1.01', '101/100'];
        yield ['-1.001', '-1001/1000'];
        yield ['-1.010', '-1010/1000'];
        yield [
            '77867087546465423456465427464560454054654.4211684848',
            '778670875464654234564654274645604540546544211684848/10000000000',
        ];
    }

    /**
     * @dataProvider providerToInt
     *
     * @param int $number The decimal number to test.
     */
    public function testToInt(int $number): void
    {
        self::assertSame($number, BigDecimal::of($number)->toInt());
        self::assertSame($number, BigDecimal::of($number . '.0')->toInt());
    }

    public function providerToInt(): Iterator
    {
        yield [PHP_INT_MIN];
        yield [-123_456_789];
        yield [-1];
        yield [0];
        yield [1];
        yield [123_456_789];
        yield [PHP_INT_MAX];
    }

    /**
     * @dataProvider providerToIntThrowsException
     *
     * @param string $number A valid decimal number that cannot safely be converted to a native integer.
     */
    public function testToIntThrowsException(string $number): void
    {
        $this->expectException(MathException::class);
        BigDecimal::of($number)->toInt();
    }

    public function providerToIntThrowsException(): Iterator
    {
        yield ['-999999999999999999999999999999'];
        yield ['9999999999999999999999999999999'];
        yield ['1.2'];
        yield ['-1.2'];
    }

    /**
     * @dataProvider providerToFloat
     *
     * @param string $value The big decimal value.
     * @param float  $float The expected float value.
     */
    public function testToFloat(string $value, float $float): void
    {
        self::assertSame($float, BigDecimal::of($value)->toFloat());
    }

    public function providerToFloat(): Iterator
    {
        yield ['0', 0.0];
        yield ['1.6', 1.6];
        yield ['-1.6', -1.6];
        yield ['9.999999999999999999999999999999999999999999999999999999999999', 9.999999999999999999999999999999];
        yield ['-9.999999999999999999999999999999999999999999999999999999999999', -9.999999999999999999999999999999];
        yield ['9.9e3000', INF];
        yield ['-9.9e3000', -INF];
    }

    /**
     * @dataProvider providerToString
     *
     * @param string $unscaledValue The unscaled value.
     * @param int    $scale         The scale.
     * @param string $expected      The expected string representation.
     */
    public function testToString(string $unscaledValue, int $scale, string $expected): void
    {
        self::assertSame($expected, (string) BigDecimal::ofUnscaledValue($unscaledValue, $scale));
    }

    public function providerToString(): Iterator
    {
        yield ['0', 0, '0'];
        yield ['0', 1, '0.0'];
        yield ['1', 1, '0.1'];
        yield ['0', 2, '0.00'];
        yield ['1', 2, '0.01'];
        yield ['10', 2, '0.10'];
        yield ['11', 2, '0.11'];
        yield ['11', 3, '0.011'];
        yield ['1', 0, '1'];
        yield ['10', 1, '1.0'];
        yield ['11', 1, '1.1'];
        yield ['100', 2, '1.00'];
        yield ['101', 2, '1.01'];
        yield ['110', 2, '1.10'];
        yield ['111', 2, '1.11'];
        yield ['111', 3, '0.111'];
        yield ['111', 4, '0.0111'];
        yield ['-1', 1, '-0.1'];
        yield ['-1', 2, '-0.01'];
        yield ['-10', 2, '-0.10'];
        yield ['-11', 2, '-0.11'];
        yield ['-12', 3, '-0.012'];
        yield ['-12', 4, '-0.0012'];
        yield ['-1', 0, '-1'];
        yield ['-10', 1, '-1.0'];
        yield ['-12', 1, '-1.2'];
        yield ['-100', 2, '-1.00'];
        yield ['-101', 2, '-1.01'];
        yield ['-120', 2, '-1.20'];
        yield ['-123', 2, '-1.23'];
        yield ['-123', 3, '-0.123'];
        yield ['-123', 4, '-0.0123'];
    }

    public function testSerialize(): void
    {
        $value = '-1234567890987654321012345678909876543210123456789';
        $scale = 37;

        $number = BigDecimal::ofUnscaledValue($value, $scale);

        self::assertBigDecimalInternalValues($value, $scale, \unserialize(\serialize($number)));
    }

    public function testDirectCallToUnserialize(): void
    {
        $this->expectException(LogicException::class);
        BigDecimal::zero()->unserialize('123:0');
    }

    /**
     * @param int         $roundingMode The rounding mode.
     * @param BigDecimal  $number       The number to round.
     * @param string      $divisor      The divisor.
     * @param string|null $two          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null $one          The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null $zero         The expected rounding to a scale of zero, or null if an exception is expected.
     */
    private function doTestRoundingMode(
        int $roundingMode,
        BigDecimal $number,
        string $divisor,
        ?string $two,
        ?string $one,
        ?string $zero
    ): void {
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
}
