<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\IntegerOverflowException;
use Brick\Math\Exception\InvalidArgumentException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Generator;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

use function is_infinite;
use function is_nan;
use function serialize;
use function sprintf;
use function unserialize;

use const INF;
use const PHP_FLOAT_EPSILON;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Unit tests for class BigRational.
 */
class BigRationalTest extends AbstractTestCase
{
    /**
     * @param int|string $numerator           The input numerator.
     * @param int|string $denominator         The input denominator.
     * @param string     $expectedNumerator   The expected numerator.
     * @param string     $expectedDenominator The expected denominator.
     */
    #[DataProvider('providerOfFraction')]
    public function testOfFraction(int|string $numerator, int|string $denominator, string $expectedNumerator, string $expectedDenominator): void
    {
        $rational = BigRational::ofFraction($numerator, $denominator);
        self::assertBigRationalInternalValues($expectedNumerator, $expectedDenominator, $rational);
    }

    public static function providerOfFraction(): array
    {
        return [
            ['7', 1, '7', '1'],
            ['7', -1, '-7', '1'],
            [7, 36, '7', '36'],
            [7, -36, '-7', '36'],
            ['-9', -15, '3', '5'],
            [1134550, '34482098458475894798273810032245500', '22691', '689641969169517895965476200644910'],
            [-899340012, '38742976492480578498793720873435125', '-99926668', '4304775165831175388754857874826125'],
            ['5858937927498353480379287328794735400', 892320015, '390595861833223565358619155252982360', '59488001'],
            ['283783947983740928034872902384095350044304', -1122233344, '-17736496748983808002179556399005959377769', '70139584'],
            ['-98765432109876543210', '12345678901234567890', '-109739369', '13717421'],
            ['9095422003440195222055833233441122284005', '-9954384195559284034403958782783723000200', '-1819084400688039044411166646688224456801', '1990876839111856806880791756556744600040'],
        ];
    }

    public function testOfFractionWithZeroDenominator(): void
    {
        $this->expectException(DivisionByZeroException::class);
        $this->expectExceptionMessageExact('The denominator of a rational number must not be zero.');

        BigRational::ofFraction(1, 0);
    }

    public function testOfFractionWithNegativeDenominator(): void
    {
        self::assertBigRationalInternalValues('-1', '2', BigRational::ofFraction(1, -2));
    }

    /**
     * @param string $string      The string to parse.
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     */
    #[DataProvider('providerOf')]
    public function testOf(string $string, string $numerator, string $denominator): void
    {
        $rational = BigRational::of($string);
        self::assertBigRationalInternalValues($numerator, $denominator, $rational);
    }

    /**
     * @param string $string      The string to parse.
     * @param string $numerator   The expected numerator.
     * @param string $denominator The expected denominator.
     */
    #[DataProvider('providerOf')]
    public function testOfNullableWithValidInputBehavesLikeOf(string $string, string $numerator, string $denominator): void
    {
        $rational = BigRational::ofNullable($string);
        self::assertBigRationalInternalValues($numerator, $denominator, $rational);
    }

    public function testOfNullableWithNullInput(): void
    {
        self::assertNull(BigRational::ofNullable(null));
    }

    public static function providerOf(): array
    {
        return [
            ['0', '0', '1'],
            ['1', '1', '1'],
            ['-1', '-1', '1'],
            ['0/123456', '0', '1'],
            ['-0/123456', '0', '1'],
            ['-1/123456', '-1', '123456'],
            ['4/6', '2', '3'],
            ['-4/6', '-2', '3'],
            ['123/456', '41', '152'],
            ['-234/567', '-26', '63'],
            ['1.125', '9', '8'],
            ['123/456', '41', '152'],
            ['+123/456', '41', '152'],
            ['-2345/6789', '-2345', '6789'],
            ['123456', '123456', '1'],
            ['-1234567', '-1234567', '1'],
            ['-0/123', '0', '1'],
            ['-1234567890987654321012345678909876543210/9999', '-137174210109739369001371742101097393690', '1111'],
            ['-1234567890987654321012345678909876543210/12345', '-82304526065843621400823045260658436214', '823'],
            ['489798742123504998877665/387590928349859112233445', '32653249474900333258511', '25839395223323940815563'],
            ['-395651984391591565172038784/445108482440540510818543632', '-8', '9'],
            ['123e4', '1230000', '1'],
            ['1.125', '9', '8'],
        ];
    }

    public function testOfWithZeroDenominator(): void
    {
        $this->expectException(DivisionByZeroException::class);
        $this->expectExceptionMessageExact('The denominator of a rational number must not be zero.');

        BigRational::of('2/0');
    }

    /**
     * @param string $string An invalid string representation.
     */
    #[DataProvider('providerOfInvalidString')]
    public function testOfInvalidString(string $string): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $string));

        BigRational::of($string);
    }

    public static function providerOfInvalidString(): array
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

    public function testZero(): void
    {
        self::assertBigRationalInternalValues('0', '1', BigRational::zero());
        self::assertSame(BigRational::zero(), BigRational::zero());
    }

    public function testOne(): void
    {
        self::assertBigRationalInternalValues('1', '1', BigRational::one());
        self::assertSame(BigRational::one(), BigRational::one());
    }

    public function testTen(): void
    {
        self::assertBigRationalInternalValues('10', '1', BigRational::ten());
        self::assertSame(BigRational::ten(), BigRational::ten());
    }

    public function testAccessors(): void
    {
        $rational = BigRational::ofFraction('7919', '578177289437982730');

        self::assertBigIntegerEquals('7919', $rational->getNumerator());
        self::assertBigIntegerEquals('578177289437982730', $rational->getDenominator());
    }

    /**
     * @param array  $values The values to compare.
     * @param string $min    The expected minimum value, in rational form.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $values, string $min): void
    {
        self::assertBigRationalEquals($min, BigRational::min(...$values));
    }

    public static function providerMin(): array
    {
        return [
            [['1/2', '1/4', '1/3'], '1/4'],
            [['1/2', '0.1', '1/3'], '1/10'],
            [['-0.25', '-0.3', '-1/8', '123456789123456789123456789', '2e25'], '-3/10'],
            [['1e30', '123456789123456789123456789/3', '2e26'], '41152263041152263041152263'],
        ];
    }

    /**
     * @param array  $values The values to compare.
     * @param string $max    The expected maximum value, in rational form.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $values, string $max): void
    {
        self::assertBigRationalEquals($max, BigRational::max(...$values));
    }

    public static function providerMax(): array
    {
        return [
            [['-5532146515641651651321321064580/32453', '-1/2', '-1/99'], '-1/99'],
            [['1e-30', '123456789123456789123456789/2', '2e25'], '123456789123456789123456789/2'],
            [['999/1000', '1'], '1'],
            [[0, '0.9', '-1.00'], '9/10'],
            [[0, '0.01', -1, '-1.2'], '1/100'],
            [['1e-30', '15185185062185185062185185048/123', '2e25'], '15185185062185185062185185048/123'],
            [['1e-30', '15185185062185185062185185048/123', '2e26'], '200000000000000000000000000'],
        ];
    }

    /**
     * @param array  $values The values to add.
     * @param string $sum    The expected sum, in rational form.
     */
    #[DataProvider('providerSum')]
    public function testSum(array $values, string $sum): void
    {
        self::assertBigRationalEquals($sum, BigRational::sum(...$values));
    }

    public static function providerSum(): array
    {
        return [
            [['-5532146515641651651321321064580/32453', '-1/2', '-1/99'], '-1095365010097047026961621574064593/6425694'],
            [['1e-30', '123456789123456789123456789/2', '2e25'], '81728394561728394561728394500000000000000000000000000001/1000000000000000000000000000000'],
            [['999/1000', '1'], '1999/1000'],
            [[0, '0.9', '-1.00'], '-1/10'],
            [[0, '0.01', -1, '-1.2'], '-219/100'],
            [['1e-30', '15185185062185185062185185048/123', '2e25'], '17645185062185185062185185048000000000000000000000000000123/123000000000000000000000000000000'],
            [['1e-30', '15185185062185185062185185048/123', '2e26'], '39785185062185185062185185048000000000000000000000000000123/123000000000000000000000000000000'],
        ];
    }

    /**
     * @param string $rational       The rational number to test.
     * @param string $integralPart   The expected integral part.
     * @param string $fractionalPart The expected fractional part.
     */
    #[DataProvider('providerGetIntegralAndFractionalPart')]
    public function testGetIntegralAndFractionalPart(string $rational, string $integralPart, string $fractionalPart): void
    {
        $r = BigRational::of($rational);

        self::assertBigIntegerEquals($integralPart, $r->getIntegralPart());
        self::assertBigRationalEquals($fractionalPart, $r->getFractionalPart());

        self::assertTrue($r->isEqualTo($r->getFractionalPart()->plus($r->getIntegralPart())));
    }

    public static function providerGetIntegralAndFractionalPart(): array
    {
        return [
            ['7/3', '2', '1/3'],
            ['-7/3', '-2', '-1/3'],
            ['3/4', '0', '3/4'],
            ['-3/4', '0', '-3/4'],
            ['22/7', '3', '1/7'],
            ['-22/7', '-3', '-1/7'],
            ['1000/3', '333', '1/3'],
            ['-1000/3', '-333', '-1/3'],
            ['895/400', '2', '19/80'],
            ['-2.5', '-2', '-1/2'],
            ['-5/2', '-2', '-1/2'],
            ['0', '0', '0'],
            ['1', '1', '0'],
            ['-1', '-1', '0'],
            ['123456789012345678901234567889/7', '17636684144620811271604938269', '6/7'],
            ['123456789012345678901234567890/7', '17636684144620811271604938270', '0'],
            ['123456789012345678901234567891/7', '17636684144620811271604938270', '1/7'],
            ['1000000000000000000000/3', '333333333333333333333', '1/3'],
            ['-999999999999999999999/7', '-142857142857142857142', '-5/7'],
        ];
    }

    /**
     * @param string               $rational The rational number to test.
     * @param BigNumber|int|string $plus     The number to add.
     * @param string               $expected The expected rational number result.
     */
    #[DataProvider('providerPlus')]
    public function testPlus(string $rational, BigNumber|int|string $plus, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->plus($plus));
    }

    public static function providerPlus(): array
    {
        return [
            ['123/456', 1, '193/152'],
            ['123/456', BigInteger::of(2), '345/152'],
            ['123/456', BigRational::ofFraction(2, 3), '427/456'],
            ['234/567', '123/28', '173/36'],
            ['-1234567890123456789/497', '79394345/109859892', '-135629495075630790047217323/54600366324'],
            ['-1234567890123456789/999', '-98765/43210', '-1185459522938548144865/959262'],
            ['123/456789123456789123456789', '-987/654321987654321', '-7156362932878877148736020/4744240749192401332533400050303375163'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $minus    The number to subtract.
     * @param string $expected The expected rational number result.
     */
    #[DataProvider('providerMinus')]
    public function testMinus(string $rational, string $minus, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->minus($minus));
    }

    public static function providerMinus(): array
    {
        return [
            ['123/456', '1', '-111/152'],
            ['234/567', '123/28', '-1003/252'],
            ['-1234567890123456789/497', '79394345/109859892', '-135629495075630868965196253/54600366324'],
            ['-1234567890123456789/999', '-98765/43210', '-1185459522938543759699/959262'],
            ['123/456789123456789123456789', '-987/654321987654321', '7156362935433848719576702/4744240749192401332533400050303375163'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $minus    The number to multiply.
     * @param string $expected The expected rational number result.
     */
    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(string $rational, string $minus, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->multipliedBy($minus));
    }

    public static function providerMultipliedBy(): array
    {
        return [
            ['123/456', '1', '41/152'],
            ['123/456', '2', '41/76'],
            ['123/456', '1/2', '41/304'],
            ['123/456', '2/3', '41/228'],
            ['-123/456', '2/3', '-41/228'],
            ['123/456', '-2/3', '-41/228'],
            ['489798742123504/387590928349859', '324893948394/23609901123', '53044215748973274183484192/3050327831503982846997219'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $minus    The number to multiply.
     * @param string $expected The expected rational number result.
     */
    #[DataProvider('providerDividedBy')]
    public function testDividedBy(string $rational, string $minus, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->dividedBy($minus));
    }

    public static function providerDividedBy(): array
    {
        return [
            ['123/456', '1', '41/152'],
            ['123/456', '2', '41/304'],
            ['123/456', '1/2', '41/76'],
            ['123/456', '2/3', '123/304'],
            ['-123/456', '2/3', '-123/304'],
            ['123/456', '-2/3', '-123/304'],
            ['489798742123504/387590928349859', '324893948394/23609901123', '1927349978617617415715832/20987657845546940253862741'],
        ];
    }

    public function testDividedByZero(): void
    {
        $number = BigRational::ofFraction(1, 2);
        $this->expectException(DivisionByZeroException::class);
        $this->expectExceptionMessageExact('Division by zero.');

        $number->dividedBy(0);
    }

    /**
     * @param string $number   The base number.
     * @param int    $exponent The exponent to apply.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerPower')]
    public function testPower(string $number, int $exponent, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($number)->power($exponent));
    }

    public static function providerPower(): array
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

            ['0', 1_000_000, '0'],
            ['1', 1_000_000, '1'],
            ['1', -1_000_000, '1'],

            ['-2/3', 99, '-633825300114114700748351602688/171792506910670443678820376588540424234035840667'],
            ['-2/3', 100, '1267650600228229401496703205376/515377520732011331036461129765621272702107522001'],

            ['-123/33', 25, '-20873554875923477449109855954682643681001/108347059433883722041830251'],
            ['123/33', 26, '855815749912862575413504094141988390921041/1191817653772720942460132761'],

            ['-123456789/2', 8, '53965948844821664748141453212125737955899777414752273389058576481/256'],
            ['9876543210/3', 7, '4191659474105327353382483648587366147848521700884465442218430000000'],

            // Negative exponents
            ['1/2',  -1, '2'],
            ['2/3',  -1, '3/2'],
            ['-3/4', -1, '-4/3'],
            ['1/3',  -1, '3'],
            ['5',    -1, '1/5'],

            ['2/3',  -2, '9/4'],
            ['-3/4', -2, '16/9'],
            ['1/2',  -2, '4'],

            ['2/3',  -3, '27/8'],
            ['-2/3', -3, '-27/8'],
            ['-1/2', -3, '-8'],

            ['-2/3', -99, '-171792506910670443678820376588540424234035840667/633825300114114700748351602688'],
            ['-2/3', -100, '515377520732011331036461129765621272702107522001/1267650600228229401496703205376'],
        ];
    }

    #[DataProvider('providerPowerWithZeroBaseAndNegativeExponent')]
    public function testPowerWithZeroBaseAndNegativeExponent(int $exponent): void
    {
        $zero = BigRational::zero();

        $this->expectException(DivisionByZeroException::class);
        $this->expectExceptionMessageExact('The reciprocal of zero is undefined.');

        $zero->power($exponent);
    }

    public static function providerPowerWithZeroBaseAndNegativeExponent(): array
    {
        return [
            [-1],
            [-2],
            [-100],
        ];
    }

    #[DataProvider('providerClamp')]
    public function testClamp(string $number, BigNumber|int|string $min, BigNumber|int|string $max, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($number)->clamp($min, $max));
    }

    public static function providerClamp(): array
    {
        return [
            ['1/2', '1/4', '3/4', '1/2'],   // within range
            ['1/8', '1/4', '3/4', '1/4'],   // below min
            ['7/8', '1/4', '3/4', '3/4'],   // above max
            ['1/4', '1/4', '3/4', '1/4'],   // equals min
            ['3/4', '1/4', '3/4', '3/4'],   // equals max
            ['-1/2', '-3/4', '-1/4', '-1/2'],  // negative range, within
            ['-1', '-3/4', '-1/4', '-3/4'],    // negative range, below min
            ['-1/8', '-3/4', '-1/4', '-1/4'],  // negative range, above max
            ['-3/4', '-3/4', '-1/4', '-3/4'],  // negative range, equals min
            ['-1/4', '-3/4', '-1/4', '-1/4'],  // negative range, equals max
            ['0', '-1/2', '1/2', '0'],         // zero within range
            ['2/3', 0, 1, '2/3'],              // int min/max
            ['3/2', '0.5', '1.0', '1'],
            ['5/4', BigRational::of('1/2'), BigRational::of('1'), '1'],  // BigRational min/max
        ];
    }

    public function testClampWithInvertedBoundsThrowsException(): void
    {
        $number = BigRational::of('1/2');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExact('The minimum value must be less than or equal to the maximum value.');

        $number->clamp('3/4', '1/4');
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected reciprocal.
     */
    #[DataProvider('providerReciprocal')]
    public function testReciprocal(string $rational, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->reciprocal());
    }

    public static function providerReciprocal(): array
    {
        return [
            ['1', '1'],
            ['2', '1/2'],
            ['1/2', '2'],
            ['123/456', '152/41'],
            ['-234/567', '-63/26'],
            ['489798742123504998877665/387590928349859112233445', '25839395223323940815563/32653249474900333258511'],
        ];
    }

    public function testReciprocalOfZeroThrowsException(): void
    {
        $number = BigRational::ofFraction(0, 2);

        $this->expectException(DivisionByZeroException::class);
        $this->expectExceptionMessageExact('The reciprocal of zero is undefined.');

        $number->reciprocal();
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected absolute number.
     */
    #[DataProvider('providerAbs')]
    public function testAbs(string $rational, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->abs());
    }

    public static function providerAbs(): array
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['-1', '1'],
            ['123/456', '41/152'],
            ['-234/567', '26/63'],
            ['-489798742123504998877665/387590928349859112233445', '32653249474900333258511/25839395223323940815563'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected negated number.
     */
    #[DataProvider('providerNegated')]
    public function testNegated(string $rational, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->negated());
    }

    public static function providerNegated(): array
    {
        return [
            ['0', '0'],
            ['1', '-1'],
            ['-1', '1'],
            ['123/456', '-41/152'],
            ['-234/567', '26/63'],
            ['-489798742123504998877665/387590928349859112233445', '32653249474900333258511/25839395223323940815563'],
            ['489798742123504998877665/387590928349859112233445', '-32653249474900333258511/25839395223323940815563'],
        ];
    }

    /**
     * @param string $rational The rational number to test.
     * @param string $expected The expected negated number.
     */
    #[DataProvider('providerSimplified')]
    public function testSimplified(string $rational, string $expected): void
    {
        self::assertBigRationalEquals($expected, BigRational::of($rational)->simplified());
    }

    public static function providerSimplified(): array
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
    public function testCompareTo(string $a, int|string $b, int $cmp): void
    {
        self::assertSame($cmp, BigRational::of($a)->compareTo($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsEqualTo(string $a, int|string $b, int $cmp): void
    {
        self::assertSame($cmp === 0, BigRational::of($a)->isEqualTo($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThan(string $a, int|string $b, int $cmp): void
    {
        self::assertSame($cmp < 0, BigRational::of($a)->isLessThan($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThanOrEqualTo(string $a, int|string $b, int $cmp): void
    {
        self::assertSame($cmp <= 0, BigRational::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThan(string $a, int|string $b, int $cmp): void
    {
        self::assertSame($cmp > 0, BigRational::of($a)->isGreaterThan($b));
    }

    /**
     * @param string     $a   The first number to compare.
     * @param int|string $b   The second number to compare.
     * @param int        $cmp The comparison value.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThanOrEqualTo(string $a, int|string $b, int $cmp): void
    {
        self::assertSame($cmp >= 0, BigRational::of($a)->isGreaterThanOrEqualTo($b));
    }

    public static function providerCompareTo(): array
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
    public function testGetSign(string $number, int $sign): void
    {
        self::assertSame($sign, BigRational::of($number)->getSign());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsZero(string $number, int $sign): void
    {
        self::assertSame($sign === 0, BigRational::of($number)->isZero());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegative(string $number, int $sign): void
    {
        self::assertSame($sign < 0, BigRational::of($number)->isNegative());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegativeOrZero(string $number, int $sign): void
    {
        self::assertSame($sign <= 0, BigRational::of($number)->isNegativeOrZero());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositive(string $number, int $sign): void
    {
        self::assertSame($sign > 0, BigRational::of($number)->isPositive());
    }

    /**
     * @param string $number The rational number to test.
     * @param int    $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositiveOrZero(string $number, int $sign): void
    {
        self::assertSame($sign >= 0, BigRational::of($number)->isPositiveOrZero());
    }

    public static function providerSign(): array
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
     * @param string|null $expected The expected integer number, or null if an exception is expected.
     */
    #[DataProvider('providerToBigInteger')]
    public function testToBigInteger(string $number, ?string $expected): void
    {
        $number = BigRational::of($number);

        if ($expected === null) {
            $this->expectException(RoundingNecessaryException::class);
            $this->expectExceptionMessageExact('This rational number cannot be represented as an integer without rounding.');
        }

        $actual = $number->toBigInteger();

        if ($expected !== null) {
            self::assertBigIntegerEquals($expected, $actual);
        }
    }

    public static function providerToBigInteger(): array
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['-1', '-1'],
            ['1/2', null],
            ['-1/2', null],
            ['2/2', '1'],
            ['-2/2', '-1'],
            ['9999999999999999999999999999999999999998', '9999999999999999999999999999999999999998'],
            ['-9999999999999999999999999999999999999998', '-9999999999999999999999999999999999999998'],
            ['9999999999999999999999999999999999999998/2', '4999999999999999999999999999999999999999'],
            ['-9999999999999999999999999999999999999998/2', '-4999999999999999999999999999999999999999'],
            ['9999999999999999999999999999999999999998/3', null],
            ['-9999999999999999999999999999999999999998/3', null],
        ];
    }

    /**
     * @param string      $number   The rational number to convert.
     * @param string|null $expected The expected decimal number, or null if an exception is expected.
     */
    #[DataProvider('providerToBigDecimal')]
    public function testToBigDecimal(string $number, ?string $expected): void
    {
        if ($expected === null) {
            $this->expectException(RoundingNecessaryException::class);
            $this->expectExceptionMessageExact('This rational number has a non-terminating decimal expansion and cannot be represented as a decimal without rounding.');
        }

        $actual = BigRational::of($number)->toBigDecimal();

        if ($expected !== null) {
            self::assertBigDecimalEquals($expected, $actual);
        }
    }

    public static function providerToBigDecimal(): Generator
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
    public function testToScale(string $number, int $scale, RoundingMode $roundingMode, string $expected): void
    {
        $number = BigRational::of($number);

        $expectedExceptionMessage = match ($expected) {
            'NON_EXACT' => 'This rational number has a non-terminating decimal expansion and cannot be represented as a decimal without rounding.',
            'SCALE_TOO_SMALL' => 'This rational number cannot be represented at the requested scale without rounding.',
            default => null,
        };

        if ($expectedExceptionMessage !== null) {
            $this->expectException(RoundingNecessaryException::class);
            $this->expectExceptionMessageExact($expectedExceptionMessage);
        }

        $actual = $number->toScale($scale, $roundingMode);

        if ($expectedExceptionMessage === null) {
            self::assertBigDecimalEquals($expected, $actual);
        }
    }

    public function testToScaleWithNegativeScale(): void
    {
        $number = BigRational::of('1/2');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExact('The scale must not be negative.');

        $number->toScale(-1);
    }

    public static function providerToScale(): array
    {
        return [
            ['1/8', 3, RoundingMode::Unnecessary, '0.125'],
            ['1/16', 3, RoundingMode::Unnecessary, 'SCALE_TOO_SMALL'],
            ['1/16', 3, RoundingMode::HalfDown, '0.062'],
            ['1/16', 3, RoundingMode::HalfUp, '0.063'],
            ['1/9', 30, RoundingMode::Down, '0.111111111111111111111111111111'],
            ['1/9', 30, RoundingMode::Up, '0.111111111111111111111111111112'],
            ['1/9', 100, RoundingMode::Unnecessary, 'NON_EXACT'],
        ];
    }

    /**
     * @param int|string $rational The rational number to test.
     * @param int        $integer  The expected integer value.
     */
    #[DataProvider('providerToInt')]
    public function testToInt(int|string $rational, int $integer): void
    {
        self::assertSame($integer, BigRational::of($rational)->toInt());
    }

    public static function providerToInt(): array
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

    #[DataProvider('providerToIntThrowsIntegerOverflowException')]
    public function testToIntThrowsIntegerOverflowException(string $number): void
    {
        $rational = BigRational::of($number);

        $this->expectException(IntegerOverflowException::class);
        $this->expectExceptionMessageExact(sprintf('%s is out of range [%d, %d] and cannot be represented as an integer.', $number, PHP_INT_MIN, PHP_INT_MAX));

        $rational->toInt();
    }

    public static function providerToIntThrowsIntegerOverflowException(): array
    {
        return [
            ['-999999999999999999999999999999'],
            ['9999999999999999999999999999999'],
        ];
    }

    #[DataProvider('providerToIntThrowsRoundingNecessaryException')]
    public function testToIntThrowsRoundingNecessaryException(string $number): void
    {
        $number = BigRational::of($number);

        $this->expectException(RoundingNecessaryException::class);
        $this->expectExceptionMessageExact('This rational number cannot be represented as an integer without rounding.');

        $number->toInt();
    }

    public static function providerToIntThrowsRoundingNecessaryException(): array
    {
        return [
            ['-9999999999999999999999999999999/2'],
            ['9999999999999999999999999999999/2'],
            ['1/2'],
            ['2/3'],
        ];
    }

    /**
     * @param BigRational|string $value    The rational number value.
     * @param float              $expected The expected float value.
     */
    #[DataProvider('providerToFloat')]
    public function testToFloat(BigRational|string $value, float $expected): void
    {
        $actual = BigRational::of($value)->toFloat();

        self::assertFalse(is_nan($actual));

        if (is_infinite($expected) || $expected === 0.0) {
            self::assertSame($expected, $actual);
        } else {
            $ratio = $actual / $expected;

            $min = 1.0 - PHP_FLOAT_EPSILON;
            $max = 1.0 + PHP_FLOAT_EPSILON;

            self::assertTrue($ratio >= $min && $ratio <= $max, sprintf('%.20f != %.20f', $actual, $expected));
        }
    }

    public static function providerToFloat(): array
    {
        return [
            ['0', 0.0],
            ['-0', 0.0],
            ['1.6', 1.6],
            ['-1.6', -1.6],
            ['1.23456789', 1.23456789],
            ['-1.23456789', -1.23456789],
            ['1000000000000000000000000000000000000000/3', 3.333333333333333e+38],
            ['-2/300000000000000000000000000000000000000', -6.666666666666666e-39],

            ['1e-100', 1e-100],
            ['-1e-100', -1e-100],
            ['1e-324', 1e-324],
            ['-1e-324', -1e-324],
            ['1e-325', 0.0],
            ['-1e-325', 0.0],
            ['1e-1000', 0.0],
            ['-1e-1000', 0.0],
            ['1.2345e-100', 1.2345e-100],
            ['-1.2345e-100', -1.2345e-100],
            ['1.2345e-1000', 0.0],
            ['-1.2345e-1000', 0.0],
            ['1e100', 1e100],
            ['-1e100', -1e100],
            ['1e308', 1e308],
            ['-1e308', -1e308],
            ['1e309', INF],
            ['-1e309', -INF],
            ['1e1000', INF],
            ['-1e1000', -INF],
            ['1.2345e100', 1.2345e100],
            ['-1.2345e100', -1.2345e100],
            ['1.2345e1000', INF],
            ['-1.2345e1000', -INF],

            [BigRational::ofFraction('1e15', BigInteger::of('1e15')->plus(1)), 0.999999999999999],
            [BigRational::ofFraction('1e15', BigInteger::of('1e15')->minus(1)), 1.000000000000001],
            [BigRational::ofFraction('-1e15', BigInteger::of('1e15')->plus(1)), -0.999999999999999],
            [BigRational::ofFraction('-1e15', BigInteger::of('1e15')->minus(1)), -1.000000000000001],
            [BigRational::ofFraction('1e1000', BigInteger::of('1e1000')->plus(1)), 1.0],
            [BigRational::ofFraction('1e1000', BigInteger::of('1e1000')->minus(1)), 1.0],
            [BigRational::ofFraction('-1e1000', BigInteger::of('1e1000')->plus(1)), -1.0],
            [BigRational::ofFraction('-1e1000', BigInteger::of('1e1000')->minus(1)), -1.0],
            [BigRational::ofFraction('1e1000', BigInteger::of('2.5e1001')->plus(1)), 0.04],
            [BigRational::ofFraction('1e1000', BigInteger::of('2.5e1001')->minus(1)), 0.04],
            [BigRational::ofFraction('-1e1000', BigInteger::of('2.5e1001')->plus(1)), -0.04],
            [BigRational::ofFraction('-1e1000', BigInteger::of('2.5e1001')->minus(1)), -0.04],
            [BigRational::ofFraction(BigInteger::of('1e1000')->plus(1), BigInteger::of('1e2000')->plus(2)), 0.0],
            [BigRational::ofFraction(BigInteger::of('-1e1000')->minus(1), BigInteger::of('1e2000')->plus(2)), 0.0],
            [BigRational::ofFraction(BigInteger::of('1.2345e9999')->plus(1), BigInteger::of('2.34e10123')->plus(2)), 5.275641025641025e-125],
            [BigRational::ofFraction(BigInteger::of('-1.2345e9999')->minus(1), BigInteger::of('2.34e10123')->plus(2)), -5.275641025641025e-125],
            [BigRational::ofFraction(BigInteger::of('1.2345e10123')->plus(3), BigInteger::of('2.34e9999')->plus(123_000)), 5.275641025641025e123],
            [BigRational::ofFraction(BigInteger::of('-1.2345e10123')->minus(3), BigInteger::of('2.34e9999')->plus(123_000)), -5.275641025641025e123],
            [BigRational::ofFraction(BigInteger::of('1e2000')->plus(1), BigInteger::of('1e1000')->plus(2)), INF],
            [BigRational::ofFraction(BigInteger::of('-1e2000')->minus(1), BigInteger::of('1e1000')->plus(2)), -INF],
            [BigRational::ofFraction(BigInteger::of('1e309'), 7), 1.4285714285714286e308],
            [BigRational::ofFraction(BigInteger::of('-1e309'), 7), -1.4285714285714286e308],
        ];
    }

    /**
     * @param string $number   The rational number.
     * @param string $expected The expected decimal representation.
     */
    #[DataProvider('providerToRepeatingDecimalString')]
    public function testToRepeatingDecimalString(string $number, string $expected): void
    {
        self::assertSame($expected, BigRational::of($number)->toRepeatingDecimalString());
    }

    public static function providerToRepeatingDecimalString(): array
    {
        return [
            ['0/7', '0'],
            ['10/5', '2'],
            ['1/2', '0.5'],
            ['1/3', '0.(3)'],
            ['4/3', '1.(3)'],
            ['10/3', '3.(3)'],
            ['7/6', '1.1(6)'],
            ['22/7', '3.(142857)'],
            ['171/70', '2.4(428571)'],
            ['122200/99', '1234.(34)'],
            ['123/98', '1.2(551020408163265306122448979591836734693877)'],
            ['1234500000/99999', '12345.(12345)'],
            ['12345000000/99999', '123451.(23451)'],
            ['1/250', '0.004'],
            ['50/8', '6.25'],
            ['1/28', '0.03(571428)'],
            ['1/40', '0.025'],
            ['-1/28', '-0.03(571428)'],
            ['-1/3', '-0.(3)'],
            ['-1/30', '-0.0(3)'],
            ['-5/2', '-2.5'],
            ['-22/7', '-3.(142857)'],
            ['1/90', '0.0(1)'],
            ['1/12', '0.08(3)'],
        ];
    }

    /**
     * @param string $numerator   The numerator.
     * @param string $denominator The denominator.
     * @param string $expected    The expected string output.
     */
    #[DataProvider('providerToString')]
    public function testToString(string $numerator, string $denominator, string $expected): void
    {
        $bigRational = BigRational::ofFraction($numerator, $denominator);
        self::assertSame($expected, $bigRational->toString());
        self::assertSame($expected, (string) $bigRational);
    }

    public static function providerToString(): array
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

    public function testSerialize(): void
    {
        $numerator = '-1234567890987654321012345678909876543210123456789';
        $denominator = '347827348278374374263874681238374983729873401984091287439827467286';

        $rational = BigRational::ofFraction($numerator, $denominator);

        self::assertBigRationalInternalValues($numerator, $denominator, unserialize(serialize($rational)));
    }

    public function testDirectCallToUnserialize(): void
    {
        $number = BigRational::ofFraction(1, 2);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageExact('__unserialize() is an internal function, it must not be called directly.');

        $number->__unserialize([]);
    }
}
