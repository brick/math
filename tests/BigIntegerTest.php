<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\Exception\IntegerOverflowException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\Internal\Calculator;
use Brick\Math\RoundingMode;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class BigInteger.
 */
class BigIntegerTest extends AbstractTestCase
{
    /**
     * @param int|float|string $value    The value to convert to a BigInteger.
     * @param string           $expected The expected string value of the result.
     */
    #[DataProvider('providerOf')]
    public function testOf(int|float|string $value, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($value));
    }

    public static function providerOf() : array
    {
        return [
            [0, '0'],
            [1, '1'],
            [-1, '-1'],
            [123456789, '123456789'],
            [-123456789, '-123456789'],
            [PHP_INT_MAX, (string) PHP_INT_MAX],
            [PHP_INT_MIN, (string) PHP_INT_MIN],

            [0.0, '0'],
            [1.0, '1'],
            [-0.0, '0'],
            [-1.0, '-1'],

            ['0.0', '0'],
            ['1.0', '1'],
            ['-1.00', '-1'],

            ['.0', '0'],
            ['.00', '0'],

            [1.2e5, '120000'],
            [-1.2e5, '-120000'],

            ['1e20', '100000000000000000000'],
            ['-3e+50', '-300000000000000000000000000000000000000000000000000'],

            ['0', '0'],
            ['+0', '0'],
            ['-0', '0'],

            ['1.', '1'],
            ['+2.', '2'],
            ['-3.', '-3'],

            ['1.e0', '1'],
            ['1.e2', '100'],
            ['-1.e2', '-100'],
            ['+1.e3', '1000'],
            ['.1e1', '1'],
            ['.1e2', '10'],
            ['-.1e2', '-10'],
            ['+.1e3', '100'],

            ['1', '1'],
            ['+1', '1'],
            ['-1', '-1'],

            ['00', '0'],
            ['+00', '0'],
            ['-00', '0'],

            ['01', '1'],
            ['+01', '1'],
            ['-01', '-1'],

            ['123456789012345678901234567890', '123456789012345678901234567890'],
            ['+123456789012345678901234567890', '123456789012345678901234567890'],
            ['-123456789012345678901234567890', '-123456789012345678901234567890'],

            ['0123456789012345678901234567890', '123456789012345678901234567890'],
            ['+0123456789012345678901234567890', '123456789012345678901234567890'],
            ['-0123456789012345678901234567890', '-123456789012345678901234567890'],

            ['00123456789012345678901234567890', '123456789012345678901234567890'],
            ['+00123456789012345678901234567890', '123456789012345678901234567890'],
            ['-00123456789012345678901234567890', '-123456789012345678901234567890'],
        ];
    }

    public function testOfBigIntegerReturnsThis() : void
    {
        $decimal = BigInteger::of(123);

        self::assertSame($decimal, BigInteger::of($decimal));
    }

    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testOfInvalidFormatThrowsException(int|float|string $value) : void
    {
        $this->expectException(NumberFormatException::class);
        BigInteger::of($value);
    }

    public static function providerOfInvalidFormatThrowsException() : array
    {
        return [
            [''],
            ['a'],
            [' 1'],
            ['1 '],
            ['+'],
            ['-'],
            ['+a'],
            ['-a'],
            ['a0'],
            ['0a'],
            ['1.a'],
            ['a.1'],
        ];
    }

    #[DataProvider('providerOfNonConvertibleValueThrowsException')]
    public function testOfNonConvertibleValueThrowsException(float|string $value) : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigInteger::of($value);
    }

    public static function providerOfNonConvertibleValueThrowsException() : array
    {
        return [
            [1.1],
            ['1e-1'],
            ['7/9'],
        ];
    }

    /**
     * @param string $number   The number to create.
     * @param int    $base     The base of the number.
     * @param string $expected The expected result in base 10.
     */
    #[DataProvider('providerFromBase')]
    public function testFromBase(string $number, int $base, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::fromBase($number, $base));
    }

    /**
     * @return array
     */
    public static function providerFromBase() : array
    {
        return [
            ['0', 10, '0'],
            ['-0', 10, '0'],
            ['+0', 10, '0'],
            ['00', 16, '0'],
            ['-00', 16, '0'],
            ['+00', 16, '0'],

            ['1', 10, '1'],
            ['-1', 10, '-1'],
            ['+1', 10, '1'],
            ['01', 8, '1'],
            ['-01', 8, '-1'],
            ['+01', 8, '1'],

            ['123', 10, '123'],
            ['+456', 10, '456'],
            ['-789', 10, '-789'],
            ['0123', 10, '123'],
            ['+0456', 10, '456'],
            ['-0789', 10, '-789'],

            ['123', 11, '146'],
            ['+456', 11, '545'],
            ['-789', 11, '-944'],
            ['0123', 11, '146'],
            ['+0456', 11, '545'],
            ['-0789', 11, '-944'],

            ['110011001100110011001111', 36, '640998479760579495168036691627608949'],
            ['110011001100110011001111', 35, '335582856048758779730579523833856636'],
            ['110011001100110011001111', 34, '172426711023004493064981145981549295'],
            ['110011001100110011001111', 33, '86853227285668653965326574185738990'],
            ['110011001100110011001111', 32, '42836489934972583913564073319498785'],
            ['110011001100110011001111', 31, '20658924711984480538771889603666144'],
            ['110011001100110011001111', 30, '9728140488839986222205212599027931'],
            ['110011001100110011001111', 29, '4465579470019956787945275674107410'],
            ['110011001100110011001111', 28, '1994689924537781753408144504465645'],
            ['110011001100110011001111', 27, '865289950909412968716094193925700'],
            ['110011001100110011001111', 26, '363729369583879309352831568000039'],
            ['110011001100110011001111', 25, '147793267388865354156500488297526'],
            ['110011001100110011001111', 24, '57888012016107577099138793486425'],
            ['110011001100110011001111', 23, '21788392294523974761749372677800'],
            ['110011001100110011001111', 22, '7852874701996329566765721637715'],
            ['110011001100110011001111', 21, '2699289081943123258094476428634'],
            ['110011001100110011001111', 20, '880809345058406615041344008421'],
            ['110011001100110011001111', 19, '271401690926468032718781859340'],
            ['110011001100110011001111', 18, '78478889737009209699633503455'],
            ['110011001100110011001111', 17, '21142384915931646646976872830'],
            ['110011001100110011001111', 16, '5261325448418072742917574929'],
            ['110011001100110011001111', 15, '1197116069565850925807253616'],
            ['110011001100110011001111', 14, '245991074299834917455374155'],
            ['110011001100110011001111', 13, '44967318722190498361960610'],
            ['110011001100110011001111', 12, '7177144825886069940574045'],
            ['110011001100110011001111', 11, '976899716207148313491924'],
            ['110011001100110011001111', 10, '110011001100110011001111'],
            ['110011001100110011001111', 9, '9849210196991880028870'],
            ['110011001100110011001111', 8, '664244955832213832265'],
            ['110011001100110011001111', 7, '31291601125492514360'],
            ['110011001100110011001111', 6, '922063395565287619'],
            ['110011001100110011001111', 5, '14328039609468906'],
            ['110011001100110011001111', 4, '88305875046485'],
            ['110011001100110011001111', 3, '127093291420'],
            ['110011001100110011001111', 2, '13421775'],

            ['ZyXwVuTsRqPoNmLkJiHgFeDcBa9876543210', 36, '106300512100105327644605138221229898724869759421181854980'],
            ['YxWvUtSrQpOnMlKjIhGfEdCbA9876543210', 35, '1101553773143634726491620528194292510495517905608180485'],
            ['XwVuTsRqPoNmLkJiHgFeDcBa9876543210', 34, '11745843093701610854378775891116314824081102660800418'],
            ['WvUtSrQpOnMlKjIhGfEdCbA9876543210', 33, '128983956064237823710866404905431464703849549412368'],
            ['VuTsRqPoNmLkJiHgFeDcBa9876543210', 32, '1459980823972598128486511383358617792788444579872'],
            ['UtSrQpOnMlKjIhGfEdCbA9876543210', 31, '17050208381689099029767742314582582184093573615'],
            ['TsRqPoNmLkJiHgFeDcBa9876543210', 30, '205646315052919334126040428061831153388822830'],
            ['SrQpOnMlKjIhGfEdCbA9876543210', 29, '2564411043271974895869785066497940850811934'],
            ['RqPoNmLkJiHgFeDcBa9876543210', 28, '33100056003358651440264672384704297711484'],
            ['QpOnMlKjIhGfEdCbA9876543210', 27, '442770531899482980347734468443677777577'],
            ['PoNmLkJiHgFeDcBa9876543210', 26, '6146269788878825859099399609538763450'],
            ['OnMlKjIhGfEdCbA9876543210', 25, '88663644327703473714387251271141900'],
            ['NmLkJiHgFeDcBa9876543210', 24, '1331214537196502869015340298036888'],
            ['MlKjIhGfEdCbA9876543210', 23, '20837326537038308910317109288851'],
            ['LkJiHgFeDcBa9876543210', 22, '340653664490377789692799452102'],
            ['KjIhGfEdCbA9876543210', 21, '5827980550840017565077671610'],
            ['JiHgFeDcBa9876543210', 20, '104567135734072022160664820'],
            ['IhGfEdCbA9876543210', 19, '1972313422155189164466189'],
            ['HgFeDcBa9876543210', 18, '39210261334551566857170'],
            ['GfEdCbA9876543210', 17, '824008854613343261192'],
            ['FeDcBa9876543210', 16, '18364758544493064720'],
            ['EdCbA9876543210', 15, '435659737878916215'],
            ['DcBa9876543210', 14, '11046255305880158'],
            ['CbA9876543210', 13, '300771807240918'],
            ['Ba9876543210', 12, '8842413667692'],
            ['A9876543210', 11, '282458553905'],
            ['9876543210', 10, '9876543210'],
            ['876543210', 9, '381367044'],
            ['76543210', 8, '16434824'],
            ['6543210', 7, '800667'],
            ['543210', 6, '44790'],
            ['43210', 5, '2930'],
            ['3210', 4, '228'],
            ['210', 3, '21'],
            ['10', 2, '2'],
        ];
    }

    #[DataProvider('providerFromBaseWithInvalidValue')]
    public function testFromBaseWithInvalidValue(string $value, int $base) : void
    {
        $this->expectException(NumberFormatException::class);
        BigInteger::fromBase($value, $base);
    }

    public static function providerFromBaseWithInvalidValue() : array
    {
        return [
            ['', 10],
            [' ', 10],
            ['+', 10],
            ['-', 10],
            ['1 ', 10],
            [' 1', 10],

            ['Z', 35],
            ['y', 34],
            ['X', 33],
            ['w', 32],
            ['V', 31],
            ['u', 30],
            ['T', 29],
            ['s', 28],
            ['R', 27],
            ['q', 26],
            ['P', 25],
            ['o', 24],
            ['N', 23],
            ['m', 22],
            ['L', 21],
            ['k', 20],
            ['J', 19],
            ['i', 18],
            ['H', 17],
            ['g', 16],
            ['F', 15],
            ['e', 14],
            ['D', 13],
            ['c', 12],
            ['B', 11],
            ['a', 10],
            ['9', 9],
            ['8', 8],
            ['7', 7],
            ['6', 6],
            ['5', 5],
            ['4', 4],
            ['3', 3],
            ['2', 2]
        ];
    }

    #[DataProvider('providerFromBaseWithInvalidBase')]
    public function testFromBaseWithInvalidBase(int $base) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::fromBase('0', $base);
    }

    public static function providerFromBaseWithInvalidBase() : array
    {
        return [
            [-2],
            [-1],
            [0],
            [1],
            [37]
        ];
    }

    public function testZero() : void
    {
        self::assertBigIntegerEquals('0', BigInteger::zero());
        self::assertSame(BigInteger::zero(), BigInteger::zero());
    }

    public function testOne() : void
    {
        self::assertBigIntegerEquals('1', BigInteger::one());
        self::assertSame(BigInteger::one(), BigInteger::one());
    }

    public function testTen() : void
    {
        self::assertBigIntegerEquals('10', BigInteger::ten());
        self::assertSame(BigInteger::ten(), BigInteger::ten());
    }

    /**
     * @param string[] $values
     */
    #[DataProvider('providerGcdMultiple')]
    public function testGcdMultiple(array $values, string $expectedGCD): void
    {
        $values = array_map(BigInteger::of(...), $values);
        $actualGCD = BigInteger::gcdMultiple(...$values);

        self::assertSame($expectedGCD, (string) $actualGCD);
    }

    public static function providerGcdMultiple(): Generator
    {
        // 1 value
        foreach (['-2', '-1', '0', '1', '2'] as $value) {
            yield [[$value], $value];
        }

        // 2 values
        foreach (self::providerGcd() as [$a, $b, $gcd]) {
            yield [[$a, $b], $gcd];
        }

        // n values
        yield [['2', '4', '7'], '1'];
        yield [['2', '4', '8'], '2'];
        yield [['2', '4', '-7'], '1'];
        yield [['2', '4', '-8'], '2'];
        yield [['28', '56', '77777'], '7'];
        yield [['28', '56', '77778'], '2'];
        yield [['28', '56', '77782'], '2'];
        yield [['28', '56', '77783'], '1'];
        yield [['28', '56', '77784'], '28'];
        yield [['28', '56', '77784', '4'], '4'];
        yield [['28', '56', '77784', '14'], '14'];
        yield [['28', '56', '77784', '14', '4'], '2'];
    }

    /**
     * @param array  $values The values to compare.
     * @param string $min    The expected minimum value.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $values, string $min) : void
    {
        self::assertBigIntegerEquals($min, BigInteger::min(... $values));
    }

    public static function providerMin() : array
    {
        return [
            [[0, 1, -1], '-1'],
            [[0, '10', '5989'], '0'],
            [[0, '10', '5989', '-1.00'], '-1'],
            [['-2/2', '1'], '-1'],
            [['-1.0', '1', '2', '-300/4', '-100'], '-100'],
            [['999999999999999999999999999', '1000000000000000000000000000'], '999999999999999999999999999'],
            [['-999999999999999999999999999', '-1000000000000000000000000000'], '-1000000000000000000000000000']
        ];
    }

    public function testMinOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::min();
    }

    public function testMinOfNonIntegerValuesThrowsException() : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigInteger::min(1, 1.2);
    }

    /**
     * @param array  $values The values to compare.
     * @param string $max    The expected maximum value.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $values, string $max) : void
    {
        self::assertBigIntegerEquals($max, BigInteger::max(... $values));
    }

    public static function providerMax() : array
    {
        return [
            [[0, 1, -1], '1'],
            [[0, '10', '5989.0'], '5989'],
            [[0, '10', '5989', '-1'], '5989'],
            [[0, '10', '5989', '-1', 6000.0], '6000'],
            [['-1', '0'], '0'],
            [['-1', '1', '2', '27/9', '-100'], '3'],
            [['999999999999999999999999999', '1000000000000000000000000000'], '1000000000000000000000000000'],
            [['-999999999999999999999999999', '-1000000000000000000000000000'], '-999999999999999999999999999']
        ];
    }

    public function testMaxOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::max();
    }

    public function testMaxOfNonIntegerValuesThrowsException() : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigInteger::max(1, '3/2');
    }

    /**
     * @param array  $values The values to add.
     * @param string $sum    The expected sum.
     */
    #[DataProvider('providerSum')]
    public function testSum(array $values, string $sum) : void
    {
        self::assertBigIntegerEquals($sum, BigInteger::sum(... $values));
    }

    public static function providerSum() : array
    {
        return [
            [[-1], '-1'],
            [[0, 1, -1], '0'],
            [[0, '10', '5989.0'], '5999'],
            [[0, '10', '5989', '-1'], '5998'],
            [[0, '10', '5989', '-1', 6000.0], '11998'],
            [['-1', '0'], '-1'],
            [['-1', '1', '2', '27/9', '-100'], '-95'],
            [['1234567', '-1233.00', 137, '406847567975012457258945126', ], '406847567975012457260178597'],
            [['-165504564654654879742303821254754', '-4455454', 455879563], '-165504564654654879742303369830645']
        ];
    }

    public function testSumOfZeroValuesThrowsException() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::sum();
    }

    public function testSumOfNonIntegerValuesThrowsException() : void
    {
        $this->expectException(RoundingNecessaryException::class);
        BigInteger::sum(1, '3/2');
    }

    /**
     * @param string $a The base number.
     * @param string $b The number to add.
     * @param string $r The expected result.
     */
    #[DataProvider('providerPlus')]
    public function testPlus(string $a, string $b, string $r) : void
    {
        self::assertBigIntegerEquals($r, BigInteger::of($a)->plus($b));
    }

    public static function providerPlus() : array
    {
        return [
            ['5165450198704521651351654564564089798441', '0', '5165450198704521651351654564564089798441'],
            ['-5165450198704521651351654564564089798441', '0', '-5165450198704521651351654564564089798441'],
            ['0', '5165450198704521651351654564564089798441', '5165450198704521651351654564564089798441'],
            ['0', '-5165450198704521651351654564564089798441', '-5165450198704521651351654564564089798441'],
            ['5165450198704521651351654564564089798441', '-5165450198704521651351654564564089798441', '0'],
            ['-5165450198704521651351654564564089798441', '5165450198704521651351654564564089798441', '0'],

            ['3493049309220392055810', '9918493493849898938928310121', '9918496986899208159320365931'],
            ['546254089287665464650654', '-4654654565726542654005465', '-4108400476438877189354811'],
            ['-54654654625426504062224', '406546504670332465465435004', '406491850015707038961372780'],
            ['-78706406576549688403246', '-3064672987984605465406546', '-3143379394561155153809792']
        ];
    }

    /**
     * @param string $a The base number.
     * @param string $b The number to subtract.
     * @param string $r The expected result.
     */
    #[DataProvider('providerMinus')]
    public function testMinus(string $a, string $b, string $r) : void
    {
        self::assertBigIntegerEquals($r, BigInteger::of($a)->minus($b));
    }

    public static function providerMinus() : array
    {
        return [
            ['5165450198704521651351654564564089798441', '0', '5165450198704521651351654564564089798441'],
            ['-5165450198704521651351654564564089798441', '0', '-5165450198704521651351654564564089798441'],
            ['0', '5165450198704521651351654564564089798441', '-5165450198704521651351654564564089798441'],
            ['0', '-5165450198704521651351654564564089798441', '5165450198704521651351654564564089798441'],

            ['879798276565798787646', '2345178709879804654605406456', '-2345177830081528088806618810'],
            ['99465465545004066406868767', '-79870987954654608076067608768', '79970453420199612142474477535'],
            ['-46465465478979879230745664', '21316504468760001807687078994', '-21362969934238981686917824658'],
            ['-2154799048440940949896046', '-9000454956465465424345404846624', '9000452801666416983404454950578']
        ];
    }

    /**
     * @param string           $a The base number.
     * @param int|float|string $b The number to multiply.
     * @param string           $r The expected result.
     */
    #[DataProvider('providerMultipliedBy')]
    public function testMultipliedBy(string $a, int|float|string $b, string $r) : void
    {
        self::assertBigIntegerEquals($r, BigInteger::of($a)->multipliedBy($b));
    }

    public static function providerMultipliedBy() : array
    {
        return [
            ['123456789098765432101234567890987654321', '1', '123456789098765432101234567890987654321'],
            ['123456789098765432101234567890987654321', '-1', '-123456789098765432101234567890987654321'],
            ['1', '123456789098765432101234567890987654321', '123456789098765432101234567890987654321'],
            ['15892588375910581333', '2485910409339228962451', '39507550875019745254366764864945838527183'],
            ['341581435989834012309', '-91050393818389238433', '-31101124267925302088072082300643257871797'],
            ['-1204902920503999920003', '1984389583950290232332', '-2390996805119422027350037939263960284136996'],
            ['-991230349304902390122', '-3483910549230593053437', '3453357870660875087266990729629471366949314'],
            ['0', '-3483910549230593053437', '0'],
            ['-991230349304902390122', '0', '0'],


            ['1274837942798479387498237897498734984', 30, '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', 30.0, '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', '30', '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', '30.0', '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', '90/3', '38245138283954381624947136924962049520'],
        ];
    }

    /**
     * @param string           $number   The base number.
     * @param int|float|string $divisor  The divisor.
     * @param string           $expected The expected result, or a class name if an exception is expected.
     */
    #[DataProvider('providerDividedBy')]
    public function testDividedBy(string $number, int|float|string $divisor, string $expected) : void
    {
        $number = BigInteger::of($number);

        if (self::isException($expected)) {
            $this->expectException($expected);
        }

        $actual = $number->dividedBy($divisor);

        if (! self::isException($expected)) {
            self::assertBigIntegerEquals($expected, $actual);
        }
    }

    public static function providerDividedBy() : array
    {
        return [
            ['123456789098765432101234567890987654321', 1, '123456789098765432101234567890987654321'],
            ['123456789098765432101234567890987654321', 2, RoundingNecessaryException::class],
            ['123456789098765432101234567890987654321', 0, DivisionByZeroException::class],
            ['123456789098765432101234567890987654321', 0.0, DivisionByZeroException::class],
            ['123456789098765432101234567890987654321', 0.1, RoundingNecessaryException::class],
            ['123456789098765432101234567890987654322', 2, '61728394549382716050617283945493827161'],
            ['123456789098765432101234567890987654322', 2.0, '61728394549382716050617283945493827161'],
            ['123456789098765432101234567890987654322', '2', '61728394549382716050617283945493827161'],
            ['123456789098765432101234567890987654322', '2.0', '61728394549382716050617283945493827161'],
            ['123456789098765432101234567890987654322', '14/7', '61728394549382716050617283945493827161'],
            ['61728394549382716050617283945493827161', '0.5', RoundingNecessaryException::class],
            ['61728394549382716050617283945493827161', '1/2', RoundingNecessaryException::class],
            ['0', '61728394549382716050617283945493827161', '0'],

            // overflowing native integer division
            ['-2147483648', '-1', '2147483648'], // 32-bit
            ['-9223372036854775808', '-1', '9223372036854775808'], // 64-bit
        ];
    }

    /**
     * @param RoundingMode $roundingMode The rounding mode.
     * @param string       $number       The number to round.
     * @param string|null  $ten          The expected rounding divided by 10, or null if an exception is expected.
     * @param string|null  $hundred      The expected rounding divided by 100 or null if an exception is expected.
     * @param string|null  $thousand     The expected rounding divided by 1000, or null if an exception is expected.
     */
    #[DataProvider('providerDividedByWithRoundingMode')]
    public function testDividedByWithRoundingMode(RoundingMode $roundingMode, string $number, ?string $ten, ?string $hundred, ?string $thousand) : void
    {
        $number = BigInteger::of($number);

        $this->doTestDividedByWithRoundingMode($roundingMode, $number, '1', $ten, $hundred, $thousand);
        $this->doTestDividedByWithRoundingMode($roundingMode, $number->negated(), '-1', $ten, $hundred, $thousand);
    }

    /**
     * @param RoundingMode $roundingMode The rounding mode.
     * @param BigInteger   $number       The number to round.
     * @param string       $divisor      The divisor.
     * @param string|null  $ten          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null  $hundred      The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null  $thousand     The expected rounding to a scale of zero, or null if an exception is expected.
     */
    private function doTestDividedByWithRoundingMode(RoundingMode $roundingMode, BigInteger $number, string $divisor, ?string $ten, ?string $hundred, ?string $thousand) : void
    {
        foreach ([$ten, $hundred, $thousand] as $expected) {
            $divisor .= '0';

            if ($expected === null) {
                $this->expectException(RoundingNecessaryException::class);
            }

            $actual = $number->dividedBy($divisor, $roundingMode);

            if ($expected !== null) {
                self::assertBigIntegerEquals($expected, $actual);
            }
        }
    }

    public static function providerDividedByWithRoundingMode() : array
    {
        return [
            [RoundingMode::UP,  '3501',  '351',  '36',  '4'],
            [RoundingMode::UP,  '3500',  '350',  '35',  '4'],
            [RoundingMode::UP,  '3499',  '350',  '35',  '4'],
            [RoundingMode::UP,  '3001',  '301',  '31',  '4'],
            [RoundingMode::UP,  '3000',  '300',  '30',  '3'],
            [RoundingMode::UP,  '2999',  '300',  '30',  '3'],
            [RoundingMode::UP,  '2501',  '251',  '26',  '3'],
            [RoundingMode::UP,  '2500',  '250',  '25',  '3'],
            [RoundingMode::UP,  '2499',  '250',  '25',  '3'],
            [RoundingMode::UP,  '2001',  '201',  '21',  '3'],
            [RoundingMode::UP,  '2000',  '200',  '20',  '2'],
            [RoundingMode::UP,  '1999',  '200',  '20',  '2'],
            [RoundingMode::UP,  '1501',  '151',  '16',  '2'],
            [RoundingMode::UP,  '1500',  '150',  '15',  '2'],
            [RoundingMode::UP,  '1499',  '150',  '15',  '2'],
            [RoundingMode::UP,  '1001',  '101',  '11',  '2'],
            [RoundingMode::UP,  '1000',  '100',  '10',  '1'],
            [RoundingMode::UP,   '999',  '100',  '10',  '1'],
            [RoundingMode::UP,   '501',   '51',   '6',  '1'],
            [RoundingMode::UP,   '500',   '50',   '5',  '1'],
            [RoundingMode::UP,   '499',   '50',   '5',  '1'],
            [RoundingMode::UP,     '1',    '1',   '1',  '1'],
            [RoundingMode::UP,     '0',    '0',   '0',  '0'],
            [RoundingMode::UP,    '-1',   '-1',  '-1', '-1'],
            [RoundingMode::UP,  '-499',  '-50',  '-5', '-1'],
            [RoundingMode::UP,  '-500',  '-50',  '-5', '-1'],
            [RoundingMode::UP,  '-501',  '-51',  '-6', '-1'],
            [RoundingMode::UP,  '-999', '-100', '-10', '-1'],
            [RoundingMode::UP, '-1000', '-100', '-10', '-1'],
            [RoundingMode::UP, '-1001', '-101', '-11', '-2'],
            [RoundingMode::UP, '-1499', '-150', '-15', '-2'],
            [RoundingMode::UP, '-1500', '-150', '-15', '-2'],
            [RoundingMode::UP, '-1501', '-151', '-16', '-2'],
            [RoundingMode::UP, '-1999', '-200', '-20', '-2'],
            [RoundingMode::UP, '-2000', '-200', '-20', '-2'],
            [RoundingMode::UP, '-2001', '-201', '-21', '-3'],
            [RoundingMode::UP, '-2499', '-250', '-25', '-3'],
            [RoundingMode::UP, '-2500', '-250', '-25', '-3'],
            [RoundingMode::UP, '-2501', '-251', '-26', '-3'],
            [RoundingMode::UP, '-2999', '-300', '-30', '-3'],
            [RoundingMode::UP, '-3000', '-300', '-30', '-3'],
            [RoundingMode::UP, '-3001', '-301', '-31', '-4'],
            [RoundingMode::UP, '-3499', '-350', '-35', '-4'],
            [RoundingMode::UP, '-3500', '-350', '-35', '-4'],
            [RoundingMode::UP, '-3501', '-351', '-36', '-4'],

            [RoundingMode::DOWN,  '3501',  '350',  '35',  '3'],
            [RoundingMode::DOWN,  '3500',  '350',  '35',  '3'],
            [RoundingMode::DOWN,  '3499',  '349',  '34',  '3'],
            [RoundingMode::DOWN,  '3001',  '300',  '30',  '3'],
            [RoundingMode::DOWN,  '3000',  '300',  '30',  '3'],
            [RoundingMode::DOWN,  '2999',  '299',  '29',  '2'],
            [RoundingMode::DOWN,  '2501',  '250',  '25',  '2'],
            [RoundingMode::DOWN,  '2500',  '250',  '25',  '2'],
            [RoundingMode::DOWN,  '2499',  '249',  '24',  '2'],
            [RoundingMode::DOWN,  '2001',  '200',  '20',  '2'],
            [RoundingMode::DOWN,  '2000',  '200',  '20',  '2'],
            [RoundingMode::DOWN,  '1999',  '199',  '19',  '1'],
            [RoundingMode::DOWN,  '1501',  '150',  '15',  '1'],
            [RoundingMode::DOWN,  '1500',  '150',  '15',  '1'],
            [RoundingMode::DOWN,  '1499',  '149',  '14',  '1'],
            [RoundingMode::DOWN,  '1001',  '100',  '10',  '1'],
            [RoundingMode::DOWN,  '1000',  '100',  '10',  '1'],
            [RoundingMode::DOWN,   '999',   '99',   '9',  '0'],
            [RoundingMode::DOWN,   '501',   '50',   '5',  '0'],
            [RoundingMode::DOWN,   '500',   '50',   '5',  '0'],
            [RoundingMode::DOWN,   '499',   '49',   '4',  '0'],
            [RoundingMode::DOWN,     '1',    '0',   '0',  '0'],
            [RoundingMode::DOWN,     '0',    '0',   '0',  '0'],
            [RoundingMode::DOWN,    '-1',    '0',   '0',  '0'],
            [RoundingMode::DOWN,  '-499',  '-49',  '-4',  '0'],
            [RoundingMode::DOWN,  '-500',  '-50',  '-5',  '0'],
            [RoundingMode::DOWN,  '-501',  '-50',  '-5',  '0'],
            [RoundingMode::DOWN,  '-999',  '-99',  '-9',  '0'],
            [RoundingMode::DOWN, '-1000', '-100', '-10', '-1'],
            [RoundingMode::DOWN, '-1001', '-100', '-10', '-1'],
            [RoundingMode::DOWN, '-1499', '-149', '-14', '-1'],
            [RoundingMode::DOWN, '-1500', '-150', '-15', '-1'],
            [RoundingMode::DOWN, '-1501', '-150', '-15', '-1'],
            [RoundingMode::DOWN, '-1999', '-199', '-19', '-1'],
            [RoundingMode::DOWN, '-2000', '-200', '-20', '-2'],
            [RoundingMode::DOWN, '-2001', '-200', '-20', '-2'],
            [RoundingMode::DOWN, '-2499', '-249', '-24', '-2'],
            [RoundingMode::DOWN, '-2500', '-250', '-25', '-2'],
            [RoundingMode::DOWN, '-2501', '-250', '-25', '-2'],
            [RoundingMode::DOWN, '-2999', '-299', '-29', '-2'],
            [RoundingMode::DOWN, '-3000', '-300', '-30', '-3'],
            [RoundingMode::DOWN, '-3001', '-300', '-30', '-3'],
            [RoundingMode::DOWN, '-3499', '-349', '-34', '-3'],
            [RoundingMode::DOWN, '-3500', '-350', '-35', '-3'],
            [RoundingMode::DOWN, '-3501', '-350', '-35', '-3'],

            [RoundingMode::CEILING,  '3501',  '351',  '36',  '4'],
            [RoundingMode::CEILING,  '3500',  '350',  '35',  '4'],
            [RoundingMode::CEILING,  '3499',  '350',  '35',  '4'],
            [RoundingMode::CEILING,  '3001',  '301',  '31',  '4'],
            [RoundingMode::CEILING,  '3000',  '300',  '30',  '3'],
            [RoundingMode::CEILING,  '2999',  '300',  '30',  '3'],
            [RoundingMode::CEILING,  '2501',  '251',  '26',  '3'],
            [RoundingMode::CEILING,  '2500',  '250',  '25',  '3'],
            [RoundingMode::CEILING,  '2499',  '250',  '25',  '3'],
            [RoundingMode::CEILING,  '2001',  '201',  '21',  '3'],
            [RoundingMode::CEILING,  '2000',  '200',  '20',  '2'],
            [RoundingMode::CEILING,  '1999',  '200',  '20',  '2'],
            [RoundingMode::CEILING,  '1501',  '151',  '16',  '2'],
            [RoundingMode::CEILING,  '1500',  '150',  '15',  '2'],
            [RoundingMode::CEILING,  '1499',  '150',  '15',  '2'],
            [RoundingMode::CEILING,  '1001',  '101',  '11',  '2'],
            [RoundingMode::CEILING,  '1000',  '100',  '10',  '1'],
            [RoundingMode::CEILING,   '999',  '100',  '10',  '1'],
            [RoundingMode::CEILING,   '501',   '51',   '6',  '1'],
            [RoundingMode::CEILING,   '500',   '50',   '5',  '1'],
            [RoundingMode::CEILING,   '499',   '50',   '5',  '1'],
            [RoundingMode::CEILING,     '1',    '1',   '1',  '1'],
            [RoundingMode::CEILING,     '0',    '0',   '0',  '0'],
            [RoundingMode::CEILING,    '-1',    '0',   '0',  '0'],
            [RoundingMode::CEILING,  '-499',  '-49' , '-4',  '0'],
            [RoundingMode::CEILING,  '-500',  '-50' , '-5',  '0'],
            [RoundingMode::CEILING,  '-501',  '-50',  '-5',  '0'],
            [RoundingMode::CEILING,  '-999',  '-99',  '-9',  '0'],
            [RoundingMode::CEILING, '-1000', '-100', '-10', '-1'],
            [RoundingMode::CEILING, '-1001', '-100', '-10', '-1'],
            [RoundingMode::CEILING, '-1499', '-149', '-14', '-1'],
            [RoundingMode::CEILING, '-1500', '-150', '-15', '-1'],
            [RoundingMode::CEILING, '-1501', '-150', '-15', '-1'],
            [RoundingMode::CEILING, '-1999', '-199', '-19', '-1'],
            [RoundingMode::CEILING, '-2000', '-200', '-20', '-2'],
            [RoundingMode::CEILING, '-2001', '-200', '-20', '-2'],
            [RoundingMode::CEILING, '-2499', '-249', '-24', '-2'],
            [RoundingMode::CEILING, '-2500', '-250', '-25', '-2'],
            [RoundingMode::CEILING, '-2501', '-250', '-25', '-2'],
            [RoundingMode::CEILING, '-2999', '-299', '-29', '-2'],
            [RoundingMode::CEILING, '-3000', '-300', '-30', '-3'],
            [RoundingMode::CEILING, '-3001', '-300', '-30', '-3'],
            [RoundingMode::CEILING, '-3499', '-349', '-34', '-3'],
            [RoundingMode::CEILING, '-3500', '-350', '-35', '-3'],
            [RoundingMode::CEILING, '-3501', '-350', '-35', '-3'],

            [RoundingMode::FLOOR,  '3501',  '350',  '35',  '3'],
            [RoundingMode::FLOOR,  '3500',  '350',  '35',  '3'],
            [RoundingMode::FLOOR,  '3499',  '349',  '34',  '3'],
            [RoundingMode::FLOOR,  '3001',  '300',  '30',  '3'],
            [RoundingMode::FLOOR,  '3000',  '300',  '30',  '3'],
            [RoundingMode::FLOOR,  '2999',  '299',  '29',  '2'],
            [RoundingMode::FLOOR,  '2501',  '250',  '25',  '2'],
            [RoundingMode::FLOOR,  '2500',  '250',  '25',  '2'],
            [RoundingMode::FLOOR,  '2499',  '249',  '24',  '2'],
            [RoundingMode::FLOOR,  '2001',  '200',  '20',  '2'],
            [RoundingMode::FLOOR,  '2000',  '200',  '20',  '2'],
            [RoundingMode::FLOOR,  '1999',  '199',  '19',  '1'],
            [RoundingMode::FLOOR,  '1501',  '150',  '15',  '1'],
            [RoundingMode::FLOOR,  '1500',  '150',  '15',  '1'],
            [RoundingMode::FLOOR,  '1499',  '149',  '14',  '1'],
            [RoundingMode::FLOOR,  '1001',  '100',  '10',  '1'],
            [RoundingMode::FLOOR,  '1000',  '100',  '10',  '1'],
            [RoundingMode::FLOOR,   '999',   '99',   '9',  '0'],
            [RoundingMode::FLOOR,   '501',   '50',   '5',  '0'],
            [RoundingMode::FLOOR,   '500',   '50',   '5',  '0'],
            [RoundingMode::FLOOR,   '499',   '49',   '4',  '0'],
            [RoundingMode::FLOOR,     '1',    '0',   '0',  '0'],
            [RoundingMode::FLOOR,     '0',    '0',   '0',  '0'],
            [RoundingMode::FLOOR,    '-1',   '-1',  '-1', '-1'],
            [RoundingMode::FLOOR,  '-499',  '-50',  '-5', '-1'],
            [RoundingMode::FLOOR,  '-500',  '-50',  '-5', '-1'],
            [RoundingMode::FLOOR,  '-501',  '-51',  '-6', '-1'],
            [RoundingMode::FLOOR,  '-999', '-100', '-10', '-1'],
            [RoundingMode::FLOOR, '-1000', '-100', '-10', '-1'],
            [RoundingMode::FLOOR, '-1001', '-101', '-11', '-2'],
            [RoundingMode::FLOOR, '-1499', '-150', '-15', '-2'],
            [RoundingMode::FLOOR, '-1500', '-150', '-15', '-2'],
            [RoundingMode::FLOOR, '-1501', '-151', '-16', '-2'],
            [RoundingMode::FLOOR, '-1999', '-200', '-20', '-2'],
            [RoundingMode::FLOOR, '-2000', '-200', '-20', '-2'],
            [RoundingMode::FLOOR, '-2001', '-201', '-21', '-3'],
            [RoundingMode::FLOOR, '-2499', '-250', '-25', '-3'],
            [RoundingMode::FLOOR, '-2500', '-250', '-25', '-3'],
            [RoundingMode::FLOOR, '-2501', '-251', '-26', '-3'],
            [RoundingMode::FLOOR, '-2999', '-300', '-30', '-3'],
            [RoundingMode::FLOOR, '-3000', '-300', '-30', '-3'],
            [RoundingMode::FLOOR, '-3001', '-301', '-31', '-4'],
            [RoundingMode::FLOOR, '-3499', '-350', '-35', '-4'],
            [RoundingMode::FLOOR, '-3500', '-350', '-35', '-4'],
            [RoundingMode::FLOOR, '-3501', '-351', '-36', '-4'],

            [RoundingMode::HALF_UP,  '3501',  '350',  '35',  '4'],
            [RoundingMode::HALF_UP,  '3500',  '350',  '35',  '4'],
            [RoundingMode::HALF_UP,  '3499',  '350',  '35',  '3'],
            [RoundingMode::HALF_UP,  '3001',  '300',  '30',  '3'],
            [RoundingMode::HALF_UP,  '3000',  '300',  '30',  '3'],
            [RoundingMode::HALF_UP,  '2999',  '300',  '30',  '3'],
            [RoundingMode::HALF_UP,  '2501',  '250',  '25',  '3'],
            [RoundingMode::HALF_UP,  '2500',  '250',  '25',  '3'],
            [RoundingMode::HALF_UP,  '2499',  '250',  '25',  '2'],
            [RoundingMode::HALF_UP,  '2001',  '200',  '20',  '2'],
            [RoundingMode::HALF_UP,  '2000',  '200',  '20',  '2'],
            [RoundingMode::HALF_UP,  '1999',  '200',  '20',  '2'],
            [RoundingMode::HALF_UP,  '1501',  '150',  '15',  '2'],
            [RoundingMode::HALF_UP,  '1500',  '150',  '15',  '2'],
            [RoundingMode::HALF_UP,  '1499',  '150',  '15',  '1'],
            [RoundingMode::HALF_UP,  '1001',  '100',  '10',  '1'],
            [RoundingMode::HALF_UP,  '1000',  '100',  '10',  '1'],
            [RoundingMode::HALF_UP,   '999',  '100',  '10',  '1'],
            [RoundingMode::HALF_UP,   '501',   '50',   '5',  '1'],
            [RoundingMode::HALF_UP,   '500',   '50',   '5',  '1'],
            [RoundingMode::HALF_UP,   '499',   '50',   '5',  '0'],
            [RoundingMode::HALF_UP,     '1',    '0',   '0',  '0'],
            [RoundingMode::HALF_UP,     '0',    '0',   '0',  '0'],
            [RoundingMode::HALF_UP,    '-1',    '0',   '0',  '0'],
            [RoundingMode::HALF_UP,  '-499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_UP,  '-500',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_UP,  '-501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_UP,  '-999', '-100', '-10', '-1'],
            [RoundingMode::HALF_UP, '-1000', '-100', '-10', '-1'],
            [RoundingMode::HALF_UP, '-1001', '-100', '-10', '-1'],
            [RoundingMode::HALF_UP, '-1499', '-150', '-15', '-1'],
            [RoundingMode::HALF_UP, '-1500', '-150', '-15', '-2'],
            [RoundingMode::HALF_UP, '-1501', '-150', '-15', '-2'],
            [RoundingMode::HALF_UP, '-1999', '-200', '-20', '-2'],
            [RoundingMode::HALF_UP, '-2000', '-200', '-20', '-2'],
            [RoundingMode::HALF_UP, '-2001', '-200', '-20', '-2'],
            [RoundingMode::HALF_UP, '-2499', '-250', '-25', '-2'],
            [RoundingMode::HALF_UP, '-2500', '-250', '-25', '-3'],
            [RoundingMode::HALF_UP, '-2501', '-250', '-25', '-3'],
            [RoundingMode::HALF_UP, '-2999', '-300', '-30', '-3'],
            [RoundingMode::HALF_UP, '-3000', '-300', '-30', '-3'],
            [RoundingMode::HALF_UP, '-3001', '-300', '-30', '-3'],
            [RoundingMode::HALF_UP, '-3499', '-350', '-35', '-3'],
            [RoundingMode::HALF_UP, '-3500', '-350', '-35', '-4'],
            [RoundingMode::HALF_UP, '-3501', '-350', '-35', '-4'],

            [RoundingMode::HALF_DOWN,  '3501',  '350',  '35',  '4'],
            [RoundingMode::HALF_DOWN,  '3500',  '350',  '35',  '3'],
            [RoundingMode::HALF_DOWN,  '3499',  '350',  '35',  '3'],
            [RoundingMode::HALF_DOWN,  '3001',  '300',  '30',  '3'],
            [RoundingMode::HALF_DOWN,  '3000',  '300',  '30',  '3'],
            [RoundingMode::HALF_DOWN,  '2999',  '300',  '30',  '3'],
            [RoundingMode::HALF_DOWN,  '2501',  '250',  '25',  '3'],
            [RoundingMode::HALF_DOWN,  '2500',  '250',  '25',  '2'],
            [RoundingMode::HALF_DOWN,  '2499',  '250',  '25',  '2'],
            [RoundingMode::HALF_DOWN,  '2001',  '200',  '20',  '2'],
            [RoundingMode::HALF_DOWN,  '2000',  '200',  '20',  '2'],
            [RoundingMode::HALF_DOWN,  '1999',  '200',  '20',  '2'],
            [RoundingMode::HALF_DOWN,  '1501',  '150',  '15',  '2'],
            [RoundingMode::HALF_DOWN,  '1500',  '150',  '15',  '1'],
            [RoundingMode::HALF_DOWN,  '1499',  '150',  '15',  '1'],
            [RoundingMode::HALF_DOWN,  '1001',  '100',  '10',  '1'],
            [RoundingMode::HALF_DOWN,  '1000',  '100',  '10',  '1'],
            [RoundingMode::HALF_DOWN,   '999',  '100',  '10',  '1'],
            [RoundingMode::HALF_DOWN,   '501',   '50',   '5',  '1'],
            [RoundingMode::HALF_DOWN,   '500',   '50',   '5',  '0'],
            [RoundingMode::HALF_DOWN,   '499',   '50',   '5',  '0'],
            [RoundingMode::HALF_DOWN,     '1',    '0',   '0',  '0'],
            [RoundingMode::HALF_DOWN,     '0',    '0',   '0',  '0'],
            [RoundingMode::HALF_DOWN,    '-1',    '0',   '0',  '0'],
            [RoundingMode::HALF_DOWN,  '-499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_DOWN,  '-500',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_DOWN,  '-501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_DOWN,  '-999', '-100', '-10', '-1'],
            [RoundingMode::HALF_DOWN, '-1000', '-100', '-10', '-1'],
            [RoundingMode::HALF_DOWN, '-1001', '-100', '-10', '-1'],
            [RoundingMode::HALF_DOWN, '-1499', '-150', '-15', '-1'],
            [RoundingMode::HALF_DOWN, '-1500', '-150', '-15', '-1'],
            [RoundingMode::HALF_DOWN, '-1501', '-150', '-15', '-2'],
            [RoundingMode::HALF_DOWN, '-1999', '-200', '-20', '-2'],
            [RoundingMode::HALF_DOWN, '-2000', '-200', '-20', '-2'],
            [RoundingMode::HALF_DOWN, '-2001', '-200', '-20', '-2'],
            [RoundingMode::HALF_DOWN, '-2499', '-250', '-25', '-2'],
            [RoundingMode::HALF_DOWN, '-2500', '-250', '-25', '-2'],
            [RoundingMode::HALF_DOWN, '-2501', '-250', '-25', '-3'],
            [RoundingMode::HALF_DOWN, '-2999', '-300', '-30', '-3'],
            [RoundingMode::HALF_DOWN, '-3000', '-300', '-30', '-3'],
            [RoundingMode::HALF_DOWN, '-3001', '-300', '-30', '-3'],
            [RoundingMode::HALF_DOWN, '-3499', '-350', '-35', '-3'],
            [RoundingMode::HALF_DOWN, '-3500', '-350', '-35', '-3'],
            [RoundingMode::HALF_DOWN, '-3501', '-350', '-35', '-4'],

            [RoundingMode::HALF_CEILING,  '3501',  '350',  '35',  '4'],
            [RoundingMode::HALF_CEILING,  '3500',  '350',  '35',  '4'],
            [RoundingMode::HALF_CEILING,  '3499',  '350',  '35',  '3'],
            [RoundingMode::HALF_CEILING,  '3001',  '300',  '30',  '3'],
            [RoundingMode::HALF_CEILING,  '3000',  '300',  '30',  '3'],
            [RoundingMode::HALF_CEILING,  '2999',  '300',  '30',  '3'],
            [RoundingMode::HALF_CEILING,  '2501',  '250',  '25',  '3'],
            [RoundingMode::HALF_CEILING,  '2500',  '250',  '25',  '3'],
            [RoundingMode::HALF_CEILING,  '2499',  '250',  '25',  '2'],
            [RoundingMode::HALF_CEILING,  '2001',  '200',  '20',  '2'],
            [RoundingMode::HALF_CEILING,  '2000',  '200',  '20',  '2'],
            [RoundingMode::HALF_CEILING,  '1999',  '200',  '20',  '2'],
            [RoundingMode::HALF_CEILING,  '1501',  '150',  '15',  '2'],
            [RoundingMode::HALF_CEILING,  '1500',  '150',  '15',  '2'],
            [RoundingMode::HALF_CEILING,  '1499',  '150',  '15',  '1'],
            [RoundingMode::HALF_CEILING,  '1001',  '100',  '10',  '1'],
            [RoundingMode::HALF_CEILING,  '1000',  '100',  '10',  '1'],
            [RoundingMode::HALF_CEILING,   '999',  '100',  '10',  '1'],
            [RoundingMode::HALF_CEILING,   '501',   '50',   '5',  '1'],
            [RoundingMode::HALF_CEILING,   '500',   '50',   '5',  '1'],
            [RoundingMode::HALF_CEILING,   '499',   '50',   '5',  '0'],
            [RoundingMode::HALF_CEILING,     '1',    '0',   '0',  '0'],
            [RoundingMode::HALF_CEILING,     '0',    '0',   '0',  '0'],
            [RoundingMode::HALF_CEILING,    '-1',    '0',   '0',  '0'],
            [RoundingMode::HALF_CEILING,  '-499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_CEILING,  '-500',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_CEILING,  '-501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_CEILING,  '-999', '-100', '-10', '-1'],
            [RoundingMode::HALF_CEILING, '-1000', '-100', '-10', '-1'],
            [RoundingMode::HALF_CEILING, '-1001', '-100', '-10', '-1'],
            [RoundingMode::HALF_CEILING, '-1499', '-150', '-15', '-1'],
            [RoundingMode::HALF_CEILING, '-1500', '-150', '-15', '-1'],
            [RoundingMode::HALF_CEILING, '-1501', '-150', '-15', '-2'],
            [RoundingMode::HALF_CEILING, '-1999', '-200', '-20', '-2'],
            [RoundingMode::HALF_CEILING, '-2000', '-200', '-20', '-2'],
            [RoundingMode::HALF_CEILING, '-2001', '-200', '-20', '-2'],
            [RoundingMode::HALF_CEILING, '-2499', '-250', '-25', '-2'],
            [RoundingMode::HALF_CEILING, '-2500', '-250', '-25', '-2'],
            [RoundingMode::HALF_CEILING, '-2501', '-250', '-25', '-3'],
            [RoundingMode::HALF_CEILING, '-2999', '-300', '-30', '-3'],
            [RoundingMode::HALF_CEILING, '-3000', '-300', '-30', '-3'],
            [RoundingMode::HALF_CEILING, '-3001', '-300', '-30', '-3'],
            [RoundingMode::HALF_CEILING, '-3499', '-350', '-35', '-3'],
            [RoundingMode::HALF_CEILING, '-3500', '-350', '-35', '-3'],
            [RoundingMode::HALF_CEILING, '-3501', '-350', '-35', '-4'],

            [RoundingMode::HALF_FLOOR,  '3501',  '350',  '35',  '4'],
            [RoundingMode::HALF_FLOOR,  '3500',  '350',  '35',  '3'],
            [RoundingMode::HALF_FLOOR,  '3499',  '350',  '35',  '3'],
            [RoundingMode::HALF_FLOOR,  '3001',  '300',  '30',  '3'],
            [RoundingMode::HALF_FLOOR,  '3000',  '300',  '30',  '3'],
            [RoundingMode::HALF_FLOOR,  '2999',  '300',  '30',  '3'],
            [RoundingMode::HALF_FLOOR,  '2501',  '250',  '25',  '3'],
            [RoundingMode::HALF_FLOOR,  '2500',  '250',  '25',  '2'],
            [RoundingMode::HALF_FLOOR,  '2499',  '250',  '25',  '2'],
            [RoundingMode::HALF_FLOOR,  '2001',  '200',  '20',  '2'],
            [RoundingMode::HALF_FLOOR,  '2000',  '200',  '20',  '2'],
            [RoundingMode::HALF_FLOOR,  '1999',  '200',  '20',  '2'],
            [RoundingMode::HALF_FLOOR,  '1501',  '150',  '15',  '2'],
            [RoundingMode::HALF_FLOOR,  '1500',  '150',  '15',  '1'],
            [RoundingMode::HALF_FLOOR,  '1499',  '150',  '15',  '1'],
            [RoundingMode::HALF_FLOOR,  '1001',  '100',  '10',  '1'],
            [RoundingMode::HALF_FLOOR,  '1000',  '100',  '10',  '1'],
            [RoundingMode::HALF_FLOOR,   '999',  '100',  '10',  '1'],
            [RoundingMode::HALF_FLOOR,   '501',   '50',   '5',  '1'],
            [RoundingMode::HALF_FLOOR,   '500',   '50',   '5',  '0'],
            [RoundingMode::HALF_FLOOR,   '499',   '50',   '5',  '0'],
            [RoundingMode::HALF_FLOOR,     '1',    '0',   '0',  '0'],
            [RoundingMode::HALF_FLOOR,     '0',    '0',   '0',  '0'],
            [RoundingMode::HALF_FLOOR,    '-1',    '0',   '0',  '0'],
            [RoundingMode::HALF_FLOOR,  '-499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_FLOOR,  '-500',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_FLOOR,  '-501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_FLOOR,  '-999', '-100', '-10', '-1'],
            [RoundingMode::HALF_FLOOR, '-1000', '-100', '-10', '-1'],
            [RoundingMode::HALF_FLOOR, '-1001', '-100', '-10', '-1'],
            [RoundingMode::HALF_FLOOR, '-1499', '-150', '-15', '-1'],
            [RoundingMode::HALF_FLOOR, '-1500', '-150', '-15', '-2'],
            [RoundingMode::HALF_FLOOR, '-1501', '-150', '-15', '-2'],
            [RoundingMode::HALF_FLOOR, '-1999', '-200', '-20', '-2'],
            [RoundingMode::HALF_FLOOR, '-2000', '-200', '-20', '-2'],
            [RoundingMode::HALF_FLOOR, '-2001', '-200', '-20', '-2'],
            [RoundingMode::HALF_FLOOR, '-2499', '-250', '-25', '-2'],
            [RoundingMode::HALF_FLOOR, '-2500', '-250', '-25', '-3'],
            [RoundingMode::HALF_FLOOR, '-2501', '-250', '-25', '-3'],
            [RoundingMode::HALF_FLOOR, '-2999', '-300', '-30', '-3'],
            [RoundingMode::HALF_FLOOR, '-3000', '-300', '-30', '-3'],
            [RoundingMode::HALF_FLOOR, '-3001', '-300', '-30', '-3'],
            [RoundingMode::HALF_FLOOR, '-3499', '-350', '-35', '-3'],
            [RoundingMode::HALF_FLOOR, '-3500', '-350', '-35', '-4'],
            [RoundingMode::HALF_FLOOR, '-3501', '-350', '-35', '-4'],

            [RoundingMode::HALF_EVEN,  '3501',  '350',  '35',  '4'],
            [RoundingMode::HALF_EVEN,  '3500',  '350',  '35',  '4'],
            [RoundingMode::HALF_EVEN,  '3499',  '350',  '35',  '3'],
            [RoundingMode::HALF_EVEN,  '3001',  '300',  '30',  '3'],
            [RoundingMode::HALF_EVEN,  '3000',  '300',  '30',  '3'],
            [RoundingMode::HALF_EVEN,  '2999',  '300',  '30',  '3'],
            [RoundingMode::HALF_EVEN,  '2501',  '250',  '25',  '3'],
            [RoundingMode::HALF_EVEN,  '2500',  '250',  '25',  '2'],
            [RoundingMode::HALF_EVEN,  '2499',  '250',  '25',  '2'],
            [RoundingMode::HALF_EVEN,  '2001',  '200',  '20',  '2'],
            [RoundingMode::HALF_EVEN,  '2000',  '200',  '20',  '2'],
            [RoundingMode::HALF_EVEN,  '1999',  '200',  '20',  '2'],
            [RoundingMode::HALF_EVEN,  '1501',  '150',  '15',  '2'],
            [RoundingMode::HALF_EVEN,  '1500',  '150',  '15',  '2'],
            [RoundingMode::HALF_EVEN,  '1499',  '150',  '15',  '1'],
            [RoundingMode::HALF_EVEN,  '1001',  '100',  '10',  '1'],
            [RoundingMode::HALF_EVEN,  '1000',  '100',  '10',  '1'],
            [RoundingMode::HALF_EVEN,   '999',  '100',  '10',  '1'],
            [RoundingMode::HALF_EVEN,   '501',   '50',   '5',  '1'],
            [RoundingMode::HALF_EVEN,   '500',   '50',   '5',  '0'],
            [RoundingMode::HALF_EVEN,   '499',   '50',   '5',  '0'],
            [RoundingMode::HALF_EVEN,     '1',    '0',   '0',  '0'],
            [RoundingMode::HALF_EVEN,     '0',    '0',   '0',  '0'],
            [RoundingMode::HALF_EVEN,    '-1',    '0',   '0',  '0'],
            [RoundingMode::HALF_EVEN,  '-499',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_EVEN,  '-500',  '-50',  '-5',  '0'],
            [RoundingMode::HALF_EVEN,  '-501',  '-50',  '-5', '-1'],
            [RoundingMode::HALF_EVEN,  '-999', '-100', '-10', '-1'],
            [RoundingMode::HALF_EVEN, '-1000', '-100', '-10', '-1'],
            [RoundingMode::HALF_EVEN, '-1001', '-100', '-10', '-1'],
            [RoundingMode::HALF_EVEN, '-1499', '-150', '-15', '-1'],
            [RoundingMode::HALF_EVEN, '-1500', '-150', '-15', '-2'],
            [RoundingMode::HALF_EVEN, '-1501', '-150', '-15', '-2'],
            [RoundingMode::HALF_EVEN, '-1999', '-200', '-20', '-2'],
            [RoundingMode::HALF_EVEN, '-2000', '-200', '-20', '-2'],
            [RoundingMode::HALF_EVEN, '-2001', '-200', '-20', '-2'],
            [RoundingMode::HALF_EVEN, '-2499', '-250', '-25', '-2'],
            [RoundingMode::HALF_EVEN, '-2500', '-250', '-25', '-2'],
            [RoundingMode::HALF_EVEN, '-2501', '-250', '-25', '-3'],
            [RoundingMode::HALF_EVEN, '-2999', '-300', '-30', '-3'],
            [RoundingMode::HALF_EVEN, '-3000', '-300', '-30', '-3'],
            [RoundingMode::HALF_EVEN, '-3001', '-300', '-30', '-3'],
            [RoundingMode::HALF_EVEN, '-3499', '-350', '-35', '-3'],
            [RoundingMode::HALF_EVEN, '-3500', '-350', '-35', '-4'],
            [RoundingMode::HALF_EVEN, '-3501', '-350', '-35', '-4'],

            [RoundingMode::UNNECESSARY,  '3501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '3500',  '350',  '35', null],
            [RoundingMode::UNNECESSARY,  '3499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '3001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '3000',  '300',  '30',  '3'],
            [RoundingMode::UNNECESSARY,  '2999',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2500',  '250',  '25', null],
            [RoundingMode::UNNECESSARY,  '2499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '2000',  '200',  '20',  '2'],
            [RoundingMode::UNNECESSARY,  '1999',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1500',  '150',  '15', null],
            [RoundingMode::UNNECESSARY,  '1499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1001',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '1000',  '100',  '10',  '1'],
            [RoundingMode::UNNECESSARY,   '999',   null,  null, null],
            [RoundingMode::UNNECESSARY,   '501',   null,  null, null],
            [RoundingMode::UNNECESSARY,   '500',   '50',   '5', null],
            [RoundingMode::UNNECESSARY,   '499',   null,  null, null],
            [RoundingMode::UNNECESSARY,     '1',   null,  null, null],
            [RoundingMode::UNNECESSARY,     '0',    '0',   '0',  '0'],
            [RoundingMode::UNNECESSARY,    '-1',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '-499',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '-500',  '-50',  '-5', null],
            [RoundingMode::UNNECESSARY,  '-501',   null,  null, null],
            [RoundingMode::UNNECESSARY,  '-999',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1000', '-100', '-10', '-1'],
            [RoundingMode::UNNECESSARY, '-1001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1500', '-150', '-15', null],
            [RoundingMode::UNNECESSARY, '-1501',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-1999',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2000', '-200', '-20', '-2'],
            [RoundingMode::UNNECESSARY, '-2001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2500', '-250', '-25', null],
            [RoundingMode::UNNECESSARY, '-2501',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-2999',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-3000', '-300', '-30', '-3'],
            [RoundingMode::UNNECESSARY, '-3001',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-3499',   null,  null, null],
            [RoundingMode::UNNECESSARY, '-3500', '-350', '-35', null],
            [RoundingMode::UNNECESSARY, '-3501',   null,  null, null],
        ];
    }

    /**
     * @param string $dividend The dividend.
     * @param string $divisor  The divisor.
     * @param string $quotient The expected quotient.
     */
    #[DataProvider('providerQuotientAndRemainder')]
    public function testQuotient(string $dividend, string $divisor, string $quotient) : void
    {
        self::assertBigIntegerEquals($quotient, BigInteger::of($dividend)->quotient($divisor));
    }

    public function testQuotientOfZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigInteger::of(1)->quotient(0);
    }

    /**
     * @param string $dividend  The dividend.
     * @param string $divisor   The divisor.
     * @param string $quotient  The expected quotient (ignored for this test).
     * @param string $remainder The expected remainder.
     */
    #[DataProvider('providerQuotientAndRemainder')]
    public function testRemainder(string $dividend, string $divisor, string $quotient, string $remainder) : void
    {
        self::assertBigIntegerEquals($remainder, BigInteger::of($dividend)->remainder($divisor));
    }

    public function testRemainderOfZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigInteger::of(1)->remainder(0);
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
        [$q, $r] = BigInteger::of($dividend)->quotientAndRemainder($divisor);

        self::assertBigIntegerEquals($quotient, $q);
        self::assertBigIntegerEquals($remainder, $r);
    }

    public static function providerQuotientAndRemainder() : array
    {
        return [
            ['1', '123', '0', '1'],
            ['1', '-123', '0', '1'],
            ['-1', '123', '0', '-1'],
            ['-1', '-123', '0', '-1'],

            ['-21', '4', '-5', '-1'],

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

            ['123456789098765432101234567890987654321', '1', '123456789098765432101234567890987654321', '0'],
            ['123456789098765432101234567890987654321', '-1', '-123456789098765432101234567890987654321', '0'],
            ['1282493059039502950823948435791053205342314', '24342491090593053', '52685366270303839158198740', '4167539367989094'],
            ['1000000000000000000000000000000000000000000000', '7777777777777777', '128571428571428584285714285714', '2232222222222222'],
            ['999999999999999999999999999999999999999999999', '22221222222', '45002025091579141312274843781092897', '13737242865'],
            ['49283205308081983923480483094304390249024223', '-23981985358744892239240813', '-2055009398548863185', '20719258837232321643854818'],
            ['-8378278174814983902084304176539029302438924', '384758527893793829309012129991', '-21775419041855', '-367584271343844173835372665619'],
            ['-444444444444444444444444444444444444411111', '-33333333333333', '13333333333333466666666666667', '-33333333300000'],

            ['922337203685477581000000000', '922337203685477580', '1000000000', '1000000000'],
            ['922337203685477581000000000', '922337203685477581', '1000000000', '0'],
        ];
    }

    public function testQuotientAndRemainderByZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigInteger::of(1)->quotientAndRemainder(0);
    }

    #[DataProvider('providerMod')]
    public function testMod(string $dividend, string $divisor, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($dividend)->mod($divisor));
    }

    public static function providerMod() : array
    {
        return [
            ['0', '1', '0'],
            ['0', '-1', '0'],

            ['1', '123', '1'],
            ['1', '-123', '-122'],
            ['-1', '123', '122'],
            ['-1', '-123', '-1'],

            ['2',   '7', '2'],
            ['2',  '-7', '-5'],
            ['-2',  '7', '5'],
            ['-2', '-7', '-2'],

            ['12',   '7', '5'],
            ['12',  '-7', '-2'],
            ['-12',  '7', '2'],
            ['-12', '-7', '-5'],

            ['123', '1', '0'],
            ['123', '-1', '0'],
            ['-123', '1', '0'],
            ['-123', '-1', '0'],

            ['123', '2', '1'],
            ['123', '-2', '-1'],
            ['-123', '2', '1'],
            ['-123', '-2', '-1'],

            ['123', '123', '0'],
            ['123', '-123', '0'],
            ['-123', '123', '0'],
            ['-123', '-123', '0'],

            ['123', '124', '123'],
            ['123', '-124', '-1'],
            ['-123', '124', '1'],
            ['-123', '-124', '-123'],

            ['124', '123', '1'],
            ['124', '-123', '-122'],
            ['-124', '123', '122'],
            ['-124', '-123', '-1'],

            ['100000000', '353467', '322306'],
            ['100000000', '-353467', '-31161'],
            ['-100000000', '353467', '31161'],
            ['-100000000', '-353467', '-322306'],

            ['1999999999999999999999999', '2000000000000000000000000', '1999999999999999999999999'],
            ['1999999999999999999999999', '-2000000000000000000000000', '-1'],
            ['-1999999999999999999999999', '2000000000000000000000000', '1'],
            ['-1999999999999999999999999', '-2000000000000000000000000', '-1999999999999999999999999'],

            ['1000000000000000000000000000000',   '3',   '1'],
            ['1000000000000000000000000000000',  '-3',  '-2'],
            ['1000000000000000000000000000000',   '9',   '1'],
            ['1000000000000000000000000000000',  '-9',  '-8'],
            ['1000000000000000000000000000000',  '11',   '1'],
            ['1000000000000000000000000000000', '-11', '-10'],
            ['1000000000000000000000000000000',  '13',   '1'],
            ['1000000000000000000000000000000', '-13', '-12'],
            ['1000000000000000000000000000000',  '21',   '1'],
            ['1000000000000000000000000000000', '-21', '-20'],

            ['123456789123456789123456789', '987654321987654321', '850308642973765431'],
            ['123456789123456789123456789', '-87654321987654321', '-22030924930968528'],
            ['-123456789123456789123456789', '7654321987654321', '5820145655913952'],
            ['-123456789123456789123456789', '-654321987654321', '-205094497790673'],

            ['123456789098765432101234567890987654321', '1', '0'],
            ['123456789098765432101234567890987654321', '-1', '0'],
            ['1282493059039502950823948435791053205342314', '24342491090593053', '4167539367989094'],
            ['1000000000000000000000000000000000000000000000', '7777777777777777', '2232222222222222'],
            ['999999999999999999999999999999999999999999999', '22221222222', '13737242865'],
            ['49283205308081983923480483094304390249024223', '-23981985358744892239240813', '-3262726521512570595385995'],
            ['-8378278174814983902084304176539029302438924', '384758527893793829309012129991', '17174256549949655473639464372'],
            ['-444444444444444444444444444444444444411111', '-33333333333333', '-33333333300000'],
        ];
    }

    public function testModZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigInteger::of(1)->mod(0);
    }

    #[DataProvider('providerModPow')]
    public function testModPow(string $base, string $exp, string $mod, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($base)->modPow($exp, $mod));
    }

    public static function providerModPow() : array
    {
        return [
            ['0', '0', '1', '0'],
            ['0', '1', '1', '0'],
            ['1', '0', '1', '0'],
            ['1', '1', '1', '0'],
            ['0', '1', '10', '0'],
            ['5', '1', '10', '5'],
            ['77', '3', '1000', '533'],
            ['11', '3', '1000', '331'],
            ['11', '7', '1000', '171'],
            ['11', '7', '900', '371'],
            ['2988348162058574136915891421498819466320163312926952423791023078876139', '2351399303373464486466122544523690094744975233415544072992656881240319', '10000000000000000000000000000000000000000', '1527229998585248450016808958343740453059']
        ];
    }

    /**
     * Crypto test from phpseclib test suite.
     */
    public function testModPowCrypto() : void
    {
        if (Calculator::get() instanceof Calculator\NativeCalculator) {
            if (getenv('CI') === 'true') {
                $this->markTestSkipped('This test is currently too slow for the CI.');
            }
        }

        $prime = BigInteger::fromBase(
            'FFFFFFFFFFFFFFFFC90FDAA22168C234C4C6628B80DC1CD1' .
            '29024E088A67CC74020BBEA63B139B22514A08798E3404DD' .
            'EF9519B3CD3A431B302B0A6DF25F14374FE1356D6D51C245' .
            'E485B576625E7EC6F44C42E9A637ED6B0BFF5CB6F406B7ED' .
            'EE386BFB5A899FA5AE9F24117C4B1FE649286651ECE45B3D' .
            'C2007CB8A163BF0598DA48361C55D39A69163FA8FD24CF5F' .
            '83655D23DCA3AD961C62F356208552BB9ED529077096966D' .
            '670C354E4ABC9804F1746C08CA18217C32905E462E36CE3B' .
            'E39E772C180E86039B2783A2EC07A28FB5C55DF06F4C52C9' .
            'DE2BCBF6955817183995497CEA956AE515D2261898FA0510' .
            '15728E5A8AACAA68FFFFFFFFFFFFFFFF',
            16
        );

        $generator = BigInteger::of(2);

        $alicePrivate = BigInteger::fromBase(
            '22606EDA7960458BC9D65F46DD96F114F9A004F0493C1F26' .
            '2139D2C8063B733162E876182CA3BF063AB1A167ABDB7F03' .
            'E0A225A6205660439F6CE46D252069FF',
            16
        );

        $bobPrivate = BigInteger::fromBase(
            '6E3EFA13A96025D63E4B0D88A09B3A46DDFE9DD3BC9D1655' .
            '4898C02B4AC181F0CEB4E818664B12F02C71A07215C400F9' .
            '88352A4779F3E88836F7C3D3B3C739DE',
            16
        );

        $alicePublic = $generator->modPow($alicePrivate, $prime);
        $bobPublic   = $generator->modPow($bobPrivate, $prime);

        $aliceShared = $bobPublic->modPow($alicePrivate, $prime);
        $bobShared   = $alicePublic->modPow($bobPrivate, $prime);

        self::assertTrue($aliceShared->isEqualTo($bobShared));
    }

    #[DataProvider('providerModPowNegativeThrowsException')]
    public function testModPowNegativeThrowsException(int $base, int $exp, int $mod) : void
    {
        $this->expectException(NegativeNumberException::class);
        BigInteger::of($base)->modPow($exp, $mod);
    }

    public static function providerModPowNegativeThrowsException() : array
    {
        return [
            [ 1,  1, -1],
            [ 1, -1,  1],
            [-1,  1,  1],
        ];
    }

    public function testModPowZeroThrowsException() : void
    {
        $this->expectException(DivisionByZeroException::class);
        BigInteger::of(1)->modPow(1, 0);
    }

    /**
     * @param string $number   The base number.
     * @param int    $exponent The exponent to apply.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerPower')]
    public function testPower(string $number, int $exponent, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($number)->power($exponent));
    }

    public static function providerPower() : array
    {
        return [
            ['-3', 0, '1'],
            ['-2', 0, '1'],
            ['-1', 0, '1'],
            ['0',  0, '1'],
            ['1',  0, '1'],
            ['2',  0, '1'],
            ['3',  0, '1'],

            ['-3', 1, '-3'],
            ['-2', 1, '-2'],
            ['-1', 1, '-1'],
            ['0',  1,  '0'],
            ['1',  1,  '1'],
            ['2',  1,  '2'],
            ['3',  1,  '3'],

            ['-3', 2, '9'],
            ['-2', 2, '4'],
            ['-1', 2, '1'],
            ['0',  2, '0'],
            ['1',  2, '1'],
            ['2',  2, '4'],
            ['3',  2, '9'],

            ['-3', 3, '-27'],
            ['-2', 3,  '-8'],
            ['-1', 3,  '-1'],
            ['0',  3,   '0'],
            ['1',  3,   '1'],
            ['2',  3,   '8'],
            ['3',  3,  '27'],

            ['0', 1000000, '0'],
            ['1', 1000000, '1'],

            ['-2', 255, '-57896044618658097711785492504343953926634992332820282019728792003956564819968'],
            [ '2', 256, '115792089237316195423570985008687907853269984665640564039457584007913129639936'],

            ['-123', 33, '-926549609804623448265268294182900512918058893428212027689876489708283'],
            [ '123', 34, '113965602005968684136628000184496763088921243891670079405854808234118809'],

            ['-123456789', 8, '53965948844821664748141453212125737955899777414752273389058576481'],
            ['9876543210', 7, '9167159269868350921847491739460569765344716959834325922131706410000000']
        ];
    }

    #[DataProvider('providerPowerWithInvalidExponentThrowsException')]
    public function testPowerWithInvalidExponentThrowsException(int $power) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::of(1)->power($power);
    }

    public static function providerPowerWithInvalidExponentThrowsException() : array
    {
        return [
            [-1],
            [1000001]
        ];
    }

    /**
     * @param string $a   The first number.
     * @param string $b   The second number.
     * @param string $gcd The expected GCD.
     */
    #[DataProvider('providerGcd')]
    public function testGcd(string $a, string $b, string $gcd) : void
    {
        $a = BigInteger::of($a);
        $b = BigInteger::of($b);

        self::assertBigIntegerEquals($gcd, $a->gcd($b));
    }

    public static function providerGcd() : \Generator
    {
        $tests = [
            ['0', '0', '0'],

            ['123456789123456789123456789123456789', '0', '123456789123456789123456789123456789'],
            ['123456789123456789123456789123456789', '1', '1'],
            ['123456789123456789123456789123456789', '2', '1'],
            ['123456789123456789123456789123456789', '3', '3'],
            ['123456789123456789123456789123456789', '4', '1'],
            ['123456789123456789123456789123456789', '5', '1'],
            ['123456789123456789123456789123456789', '6', '3'],
            ['123456789123456789123456789123456789', '7', '7'],
            ['123456789123456789123456789123456789', '8', '1'],
            ['123456789123456789123456789123456789', '9', '9'],
            ['123456789123456789123456789123456789', '10', '1'],
            ['123456789123456789123456789123456789', '11', '11'],
            ['123456789123456789123456789123456789', '12', '3'],
            ['123456789123456789123456789123456789', '13', '13'],
            ['123456789123456789123456789123456789', '14', '7'],
            ['123456789123456789123456789123456789', '15', '3'],
            ['123456789123456789123456789123456789', '16', '1'],
            ['123456789123456789123456789123456789', '17', '1'],
            ['123456789123456789123456789123456789', '18', '9'],
            ['123456789123456789123456789123456789', '19', '19'],
            ['123456789123456789123456789123456789', '20', '1'],
            ['123456789123456789123456789123456789', '100', '1'],
            ['123456789123456789123456789123456789', '101', '101'],
            ['123456789123456789123456789123456789', '102', '3'],
            ['123456789123456789123456789123456789', '103', '1'],
            ['123456789123456789123456789123456789', '104', '13'],
            ['123456789123456789123456789123456789', '105', '21'],
            ['123456789123456789123456789123456789', '985', '1'],
            ['123456789123456789123456789123456789', '986', '1'],
            ['123456789123456789123456789123456789', '987', '21'],
            ['123456789123456789123456789123456789', '988', '247'],
            ['123456789123456789123456789123456789', '989', '1'],
            ['123456789123456789123456789123456789', '990', '99'],
            ['123456789123456789123456789123456789', '10010', '1001'],
            ['123456789123456789123456789123456789', '10017', '63'],
            ['123456789123456789123456789123456789', '10089', '171'],
            ['123456789123456789123456789123456789', '10098', '99'],
            ['123456789123456789123456789123456789', '100035', '2223'],
            ['123456789123456789123456789123456789', '1000065', '627'],
            ['123456789123456789123456789123456789', '10000068', '39'],
            ['123456789123456789123456789123456789', '10001222', '7777'],
            ['123456789123456789123456789123456789', '10001277', '24453'],
            ['123456789123456789123456789123456789', '100005258', '157737'],
            ['123456789123456789123456789123456789', '100010001', '2702973'],
            ['123456789123456789123456789123456789', '100148202', '50074101'],
            ['123456789123456789123456789123456789', '100478469', '14354067'],
            ['123456789123456789123456789123456789', '123456789140121129', '8249681517'],
            ['123456789123456789123456789123456789', '123456789150891631', '1385459521'],
            ['123456789123456789123456789123456789', '123456789192058322', '6928754833'],
            ['123456789123456789123456789123456789', '123456789202361433', '1992342261'],
            ['123456789123456789123456789123456789', '999456789162772941', '20786264499'],
            ['123456789123456789123456789123456789', '999456789176548074', '2345678991'],
            ['123456789123456789123456789123456789', '999456789188938248', '2372170689'],

            ['88888777776666655555444443333322222111110000099999', '100000000011886128', '8252579097'],
            ['88888777776666655555444443333322222111110000099999', '100000000013330403', '1162443303'],
            ['88888777776666655555444443333322222111110000099999', '100000000020221920', '2470053077'],
            ['88888777776666655555444443333322222111110000099999', '100000000031937250', '3970893353'],
            ['88888777776666655555444443333322222111110000099999', '100000000043341848', '1102420413'],
            ['88888777776666655555444443333322222111110000099999', '100000000047565681', '1212240071'],
            ['88888777776666655555444443333322222111110000099999', '100000000065586124', '1172302873'],
            ['88888777776666655555444443333322222111110000099999', '100000000068684846', '1051734417'],
            ['88888777776666655555444443333322222111110000099999', '100000000068736887', '2423071539'],
        ];

        foreach ($tests as [$a, $b, $gcd]) {
            yield [$a, $b, $gcd];
            yield [$b, $a, $gcd];

            yield [$a, "-$b", $gcd];
            yield [$b, "-$a", $gcd];

            yield ["-$a", $b, $gcd];
            yield ["-$b", $a, $gcd];

            yield ["-$a", "-$b", $gcd];
            yield ["-$b", "-$a", $gcd];
        }
    }

    #[DataProvider('providerSqrt')]
    public function testSqrt(string $number, string $sqrt) : void
    {
        $number = BigInteger::of($number);

        self::assertBigIntegerEquals($sqrt, $number->sqrt());
    }

    public static function providerSqrt() : array
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['3', '1'],
            ['4', '2'],
            ['8', '2'],
            ['9', '3'],
            ['15', '3'],
            ['16', '4'],
            ['24', '4'],
            ['25', '5'],
            ['35', '5'],
            ['36', '6'],
            ['48', '6'],
            ['49', '7'],
            ['63', '7'],
            ['64', '8'],
            ['80', '8'],
            ['81', '9'],
            ['99', '9'],
            ['100', '10'],

            ['536137214136734800142146901786039940282473271927911507640625', '732213912826528310663262741625'],
            ['536137214136734800142146901787504368108126328549238033123875', '732213912826528310663262741625'],
            ['536137214136734800142146901787504368108126328549238033123876', '732213912826528310663262741626'],
            ['5651495859544574019979802175954184725583245698990648064256', '75176431543034642899535752016'],
            ['5651495859544574019979802176104537588669314984789719568288', '75176431543034642899535752016'],
            ['5651495859544574019979802176104537588669314984789719568289', '75176431543034642899535752017'],
            ['303791344535904055863813752643405021077478121784571407761729', '551172699374618931119694672223'],
            ['303791344535904055863813752644507366476227359646810797106175', '551172699374618931119694672223'],
            ['303791344535904055863813752644507366476227359646810797106176', '551172699374618931119694672224'],
            ['563247882222081148212230590155705163466381822617812257437849', '750498422531374244468346132293'],
            ['563247882222081148212230590157206160311444571106748949702435', '750498422531374244468346132293'],
            ['563247882222081148212230590157206160311444571106748949702436', '750498422531374244468346132294'],
            ['396189426113498637323382117462633843296333578307938768136329', '629435799834660371475569807677'],
            ['396189426113498637323382117463892714896002899050889907751683', '629435799834660371475569807677'],
            ['396189426113498637323382117463892714896002899050889907751684', '629435799834660371475569807678'],
            ['80479328054895287914569547663334257436697869675619064535360356', '8971027146034911420307518533066'],
            ['80479328054895287914569547663352199490989939498459679572426488', '8971027146034911420307518533066'],
            ['80479328054895287914569547663352199490989939498459679572426489', '8971027146034911420307518533067'],
            ['681849477075053403257629437089098232501847587591122144140625', '825741773846432819101899135625'],
            ['681849477075053403257629437090749716049540453229325942411875', '825741773846432819101899135625'],
            ['681849477075053403257629437090749716049540453229325942411876', '825741773846432819101899135626'],
            ['74137461815059419705618984794190691016668430175974095624129600', '8610311365743948501801103299640'],
            ['74137461815059419705618984794207911639399918072977697830728880', '8610311365743948501801103299640'],
            ['74137461815059419705618984794207911639399918072977697830728881', '8610311365743948501801103299641'],
            ['6679657010858483937903991845397665010485952775098785560735409', '2584503242570704134867997320903'],
            ['6679657010858483937903991845402834016971094183368521555377215', '2584503242570704134867997320903'],
            ['6679657010858483937903991845402834016971094183368521555377216', '2584503242570704134867997320904'],
            ['76167329888878964758237229146072291137608533898056659182403025', '8727389637736988579847341871305'],
            ['76167329888878964758237229146089745916884007875216353866145635', '8727389637736988579847341871305'],
            ['76167329888878964758237229146089745916884007875216353866145636', '8727389637736988579847341871306'],
            ['6743391560146249598812429163892766868360953827745248726306064', '2596804105077287033903247753508'],
            ['6743391560146249598812429163897960476571108401813055221813080', '2596804105077287033903247753508'],
            ['6743391560146249598812429163897960476571108401813055221813081', '2596804105077287033903247753509'],
            ['1867791142724588955955814039055642821336323719154250751224723556', '43217949311884164825638553209334'],
            ['1867791142724588955955814039055729257234947487483902028331142224', '43217949311884164825638553209334'],
            ['1867791142724588955955814039055729257234947487483902028331142225', '43217949311884164825638553209335'],
            ['9830504148113505106730565430925806810010750548232214925001449636', '99148898874942151859351963400806'],
            ['9830504148113505106730565430926005107808500432535933628928251248', '99148898874942151859351963400806'],
            ['9830504148113505106730565430926005107808500432535933628928251249', '99148898874942151859351963400807'],
            ['7591171119877361934423335814652578116545218322867005352922382400', '87127327055737007353139169677320'],
            ['7591171119877361934423335814652752371199329796881711631261737040', '87127327055737007353139169677320'],
            ['7591171119877361934423335814652752371199329796881711631261737041', '87127327055737007353139169677321'],
            ['8534704621867005063010169093165229825021562891964253458234113600', '92383465089089427718873950884440'],
            ['8534704621867005063010169093165414591951741070819691206135882480', '92383465089089427718873950884440'],
            ['8534704621867005063010169093165414591951741070819691206135882481', '92383465089089427718873950884441'],
            ['558060557515620507133380963948250962461823927030515012873505842176', '747034508918845399769072043504224'],
            ['558060557515620507133380963948252456530841764721314551017592850624', '747034508918845399769072043504224'],
            ['558060557515620507133380963948252456530841764721314551017592850625', '747034508918845399769072043504225'],
            ['200538408436782201630574692476963942210287537291410052113367162944', '447815149851791192871220236705288'],
            ['200538408436782201630574692476964837840587240873795794553840573520', '447815149851791192871220236705288'],
            ['200538408436782201630574692476964837840587240873795794553840573521', '447815149851791192871220236705289'],
            ['191302866278956628715433979906440851661028066577090295375612311364', '437381831217251900054617942999442'],
            ['191302866278956628715433979906441726424690501080890404611498310248', '437381831217251900054617942999442'],
            ['191302866278956628715433979906441726424690501080890404611498310249', '437381831217251900054617942999443'],
            ['291290137244253022092289121327605756463305738312066948462328733721', '539713013780706442298424414941189'],
            ['291290137244253022092289121327606835889333299724951545311158616099', '539713013780706442298424414941189'],
            ['291290137244253022092289121327606835889333299724951545311158616100', '539713013780706442298424414941190'],
            ['5025051069729848944739380852251409470255071569439258719898537481', '70887594610974415152598103986941'],
            ['5025051069729848944739380852251551245444293518269563916106511363', '70887594610974415152598103986941'],
            ['5025051069729848944739380852251551245444293518269563916106511364', '70887594610974415152598103986942'],
            ['6238861109923404442965423418956764090860907116154205636120522931561', '2497771228500201225537381791181331'],
            ['6238861109923404442965423418956769086403364116556656710884105294223', '2497771228500201225537381791181331'],
            ['6238861109923404442965423418956769086403364116556656710884105294224', '2497771228500201225537381791181332'],
            ['20023427755036504641354330839858631034820554187187074374005311969476', '4474754491034843873667293532006926'],
            ['20023427755036504641354330839858639984329536256874821708592375983328', '4474754491034843873667293532006926'],
            ['20023427755036504641354330839858639984329536256874821708592375983329', '4474754491034843873667293532006927'],
            ['35963041045659882033621773487569203358608603414532908772894092442881', '5996919296243687322881924437376641'],
            ['35963041045659882033621773487569215352447195901907554536742967196163', '5996919296243687322881924437376641'],
            ['35963041045659882033621773487569215352447195901907554536742967196164', '5996919296243687322881924437376642'],
            ['16147491963439711944418728362110466093273207282720057861529178507569', '4018394202096119880426988697249913'],
            ['16147491963439711944418728362110474130061611474959818715506573007395', '4018394202096119880426988697249913'],
            ['16147491963439711944418728362110474130061611474959818715506573007396', '4018394202096119880426988697249914'],
            ['59275127168144151414318593405790938619095816316145068714478973756416', '7699034171124593171733765899358304'],
            ['59275127168144151414318593405790954017164158565331412182010772473024', '7699034171124593171733765899358304'],
            ['59275127168144151414318593405790954017164158565331412182010772473025', '7699034171124593171733765899358305'],
            ['5285010890475466266241012129886993921224228841648175760159220822102500', '72698080376826087595732320834198550'],
            ['5285010890475466266241012129886994066620389595300350951623862490499600', '72698080376826087595732320834198550'],
            ['5285010890475466266241012129886994066620389595300350951623862490499601', '72698080376826087595732320834198551'],
            ['138806460367120633162909304707973463857407594119348580175849319475249', '11781615354743195534745216445516807'],
            ['138806460367120633162909304707973487420638303605739649666282210508863', '11781615354743195534745216445516807'],
            ['138806460367120633162909304707973487420638303605739649666282210508864', '11781615354743195534745216445516808'],
            ['4394593323188981215423145755036811566487951532301290484907917429040356', '66291728919896041699308382947726934'],
            ['4394593323188981215423145755036811699071409372093373883524683324494224', '66291728919896041699308382947726934'],
            ['4394593323188981215423145755036811699071409372093373883524683324494225', '66291728919896041699308382947726935'],
            ['2165102898894822021490897916929619474531175829310332070795169853731396', '46530666220190980857729715988167086'],
            ['2165102898894822021490897916929619567592508269692293786254601830065568', '46530666220190980857729715988167086'],
            ['2165102898894822021490897916929619567592508269692293786254601830065569', '46530666220190980857729715988167087'],
            ['3387136764299263076201461182231668509909087521737501460409373154998724', '58199113088596660127381090812371918'],
            ['3387136764299263076201461182231668626307313698930821715171554779742560', '58199113088596660127381090812371918'],
            ['3387136764299263076201461182231668626307313698930821715171554779742561', '58199113088596660127381090812371919'],
            ['598571098379632349988804996185789284322923089459844190238738309900665041', '773673767410807557353849214204230071'],
            ['598571098379632349988804996185789285870270624281459304946436738309125183', '773673767410807557353849214204230071'],
            ['598571098379632349988804996185789285870270624281459304946436738309125184', '773673767410807557353849214204230072'],
            ['520654040364073618548858305514718008281370888178241149423087536697602769', '721563607982050061031165602524215113'],
            ['520654040364073618548858305514718009724498104142341271485418741746032995', '721563607982050061031165602524215113'],
            ['520654040364073618548858305514718009724498104142341271485418741746032996', '721563607982050061031165602524215114'],
            ['49468236740249321869413367805106481368010073927095236023233757323825636', '222414560540107899360763128216988694'],
            ['49468236740249321869413367805106481812839195007311034744760013757803024', '222414560540107899360763128216988694'],
            ['49468236740249321869413367805106481812839195007311034744760013757803025', '222414560540107899360763128216988695'],
            ['752985146631183783668626620327136132189678265369154785125113211857131025', '867747167457885617308484616342840855'],
            ['752985146631183783668626620327136133925172600284926019742082444542812735', '867747167457885617308484616342840855'],
            ['752985146631183783668626620327136133925172600284926019742082444542812736', '867747167457885617308484616342840856'],
            ['4964939306071472434861762119449573856931127119323966160723135793312400', '70462325437580276008386497095248180'],
            ['4964939306071472434861762119449573997855777994484518177496129983808760', '70462325437580276008386497095248180'],
            ['4964939306071472434861762119449573997855777994484518177496129983808761', '70462325437580276008386497095248181'],
            ['6793060375719308872947059218122494900670395578602098991588961245675868921', '2606350010209547598885835947629243339'],
            ['6793060375719308872947059218122494905883095599021194189360633140934355599', '2606350010209547598885835947629243339'],
            ['6793060375719308872947059218122494905883095599021194189360633140934355600', '2606350010209547598885835947629243340'],
            ['72434023187588979108181351955877408189952248642066292273939239044048882404', '8510818009309620944867597322534681898'],
            ['72434023187588979108181351955877408206973884660685534163674433689118246200', '8510818009309620944867597322534681898'],
            ['72434023187588979108181351955877408206973884660685534163674433689118246201', '8510818009309620944867597322534681899'],
            ['11820228792957342809567646506028581034714829445539021053517615632467438404', '3438055961289365459878259691983242898'],
            ['11820228792957342809567646506028581041590941368117751973274135016433924200', '3438055961289365459878259691983242898'],
            ['11820228792957342809567646506028581041590941368117751973274135016433924201', '3438055961289365459878259691983242899'],
            ['6386392982997549868964551665256601064299977946311952729197080155224215225', '2527131374305172498893486771925871765'],
            ['6386392982997549868964551665256601069354240694922297726984053699075958755', '2527131374305172498893486771925871765'],
            ['6386392982997549868964551665256601069354240694922297726984053699075958756', '2527131374305172498893486771925871766'],
            ['241100595746204845568053694673173646954637963055159189070656778312291856', '491019954529553559943132903540543684'],
            ['241100595746204845568053694673173647936677872114266308956922585393379224', '491019954529553559943132903540543684'],
            ['241100595746204845568053694673173647936677872114266308956922585393379225', '491019954529553559943132903540543685'],
            ['9443978332159357501114101827797274207266380117275222876917889364528773973136', '97180133423243238008221615153136002444'],
            ['9443978332159357501114101827797274207460740384121709352934332594835045978024', '97180133423243238008221615153136002444'],
            ['9443978332159357501114101827797274207460740384121709352934332594835045978025', '97180133423243238008221615153136002445'],
            ['313018699256618409001992020847417266900442089718999098874457999889859130596', '17692334477298873658720698607769718186'],
            ['313018699256618409001992020847417266935826758673596846191899397105398566968', '17692334477298873658720698607769718186'],
            ['313018699256618409001992020847417266935826758673596846191899397105398566969', '17692334477298873658720698607769718187'],
            ['6933071107478585245042253386666659991142755133413332931821052899578751610000', '83265065348431602879537003312633461900'],
            ['6933071107478585245042253386666659991309285264110196137580126906204018533800', '83265065348431602879537003312633461900'],
            ['6933071107478585245042253386666659991309285264110196137580126906204018533801', '83265065348431602879537003312633461901'],
            ['1579670801917993723309858041679990418433600770326208765934116379197768903056', '39745072674710178762237903560835952916'],
            ['1579670801917993723309858041679990418513090915675629123458592186319440808888', '39745072674710178762237903560835952916'],
            ['1579670801917993723309858041679990418513090915675629123458592186319440808889', '39745072674710178762237903560835952917'],
            ['681867337215944596936584982759400170727379727957999719338051977506244355625', '26112589630596667363860934899092645325'],
            ['681867337215944596936584982759400170779604907219193054065773847304429646275', '26112589630596667363860934899092645325'],
            ['681867337215944596936584982759400170779604907219193054065773847304429646276', '26112589630596667363860934899092645326'],
            ['288453689043393924123357086972242613663177706471101442929325163584978377458225', '537078848069251303040109060485183727865'],
            ['288453689043393924123357086972242613664251864167239945535405381705948744913955', '537078848069251303040109060485183727865'],
            ['288453689043393924123357086972242613664251864167239945535405381705948744913956', '537078848069251303040109060485183727866'],
            ['195356853506305520718626478206756382507801939519785697420547945042825319176129', '441991915657182038371378633304634321377'],
            ['195356853506305520718626478206756382508685923351100061497290702309434587818883', '441991915657182038371378633304634321377'],
            ['195356853506305520718626478206756382508685923351100061497290702309434587818884', '441991915657182038371378633304634321378'],
            ['171318600916753697429051575925878633930439101417068058777561592608661184180625', '413906512290823587410087929387066009175'],
            ['171318600916753697429051575925878633931266914441649705952381768467435316198975', '413906512290823587410087929387066009175'],
            ['171318600916753697429051575925878633931266914441649705952381768467435316198976', '413906512290823587410087929387066009176'],
            ['921093761539400396463886664522151015280465523566321840991339256462222570006049', '959736297916985317802888407093394929007'],
            ['921093761539400396463886664522151015282384996162155811626945033276409359864063', '959736297916985317802888407093394929007'],
            ['921093761539400396463886664522151015282384996162155811626945033276409359864064', '959736297916985317802888407093394929008'],
            ['95109948153362184845030342755869423838311917496546178503410149505934962822401', '308399008029147501090828356257276909951'],
            ['95109948153362184845030342755869423838928715512604473505591806218449516642303', '308399008029147501090828356257276909951'],
            ['95109948153362184845030342755869423838928715512604473505591806218449516642304', '308399008029147501090828356257276909952'],
            ['19331455316972407468074740850649574350712359000359926791488814669406316705438096', '4396755089491841080442863075541933084436'],
            ['19331455316972407468074740850649574350721152510538910473649700395557400571606968', '4396755089491841080442863075541933084436'],
            ['19331455316972407468074740850649574350721152510538910473649700395557400571606969', '4396755089491841080442863075541933084437'],
            ['79265978575272061761010812898504289157311811916595218347414644480274502855387716', '8903144308348150197415041401715992316846'],
            ['79265978575272061761010812898504289157329618205211914647809474563077934840021408', '8903144308348150197415041401715992316846'],
            ['79265978575272061761010812898504289157329618205211914647809474563077934840021409', '8903144308348150197415041401715992316847'],
            ['18253381792846313534444974681107507939609666705140666138048641067680220712870544', '4272397663238560654482324050372517154612'],
            ['18253381792846313534444974681107507939618211500467143259357605715780965747179768', '4272397663238560654482324050372517154612'],
            ['18253381792846313534444974681107507939618211500467143259357605715780965747179769', '4272397663238560654482324050372517154613'],
            ['34902249932301330884717409262136201131799911786648110181019008640668850248149889', '5907812618245549169232055936851452816833'],
            ['34902249932301330884717409262136201131811727411884601279357472752542553153783555', '5907812618245549169232055936851452816833'],
            ['34902249932301330884717409262136201131811727411884601279357472752542553153783556', '5907812618245549169232055936851452816834'],
            ['2599345980007138866704208379685556534258757776044753601160948822029798157394521', '1612248733913951277041847563008484168539'],
            ['2599345980007138866704208379685556534261982273512581503715032517155815125731599', '1612248733913951277041847563008484168539'],
            ['2599345980007138866704208379685556534261982273512581503715032517155815125731600', '1612248733913951277041847563008484168540'],
            ['5316322030460586450936871958946919636668068288991234947266697538297768830115066084', '72913112884175960485473057702832007114922'],
            ['5316322030460586450936871958946919636668214115217003299187668484413174494129295928', '72913112884175960485473057702832007114922'],
            ['5316322030460586450936871958946919636668214115217003299187668484413174494129295929', '72913112884175960485473057702832007114923'],
            ['8002778973791813108451518291518896929427763054970205412144571951733372499281017601', '89458252686891962949549637560732937788801'],
            ['8002778973791813108451518291518896929427941971475579196070471051008493965156595203', '89458252686891962949549637560732937788801'],
            ['8002778973791813108451518291518896929427941971475579196070471051008493965156595204', '89458252686891962949549637560732937788802'],
            ['204434819803436425073091821729105629106597079412374857081154364351611450036288004', '14298070492322956269314619104943213072002'],
            ['204434819803436425073091821729105629106625675553359502993692993589821336462432008', '14298070492322956269314619104943213072002'],
            ['204434819803436425073091821729105629106625675553359502993692993589821336462432009', '14298070492322956269314619104943213072003'],
            ['248985683905018963214400101508836326950598468017331056913175271121208335269369616', '15779280208711009616855603241137879133796'],
            ['248985683905018963214400101508836326950630026577748478932408982327690611027637208', '15779280208711009616855603241137879133796'],
            ['248985683905018963214400101508836326950630026577748478932408982327690611027637209', '15779280208711009616855603241137879133797'],
            ['9931466785180918581453369553241893953494729509958339130768488436428313616867737664', '99656744805261016419561454808199789423608'],
            ['9931466785180918581453369553241893953494928823447949652801327559337930016446584880', '99656744805261016419561454808199789423608'],
            ['9931466785180918581453369553241893953494928823447949652801327559337930016446584881', '99656744805261016419561454808199789423609'],
            ['571034337207963083686644289650754887712756826847796234542618077049856839883774610496', '755668139600951472909394170487042707063864'],
            ['571034337207963083686644289650754887712758338184075436445563895838197813969188738224', '755668139600951472909394170487042707063864'],
            ['571034337207963083686644289650754887712758338184075436445563895838197813969188738225', '755668139600951472909394170487042707063865'],
            ['43282087429518774838368965030190873132157640943271155661308846536619714287766015625', '208043474854461068334845359605621664088125'],
            ['43282087429518774838368965030190873132158057030220864583445516227338925531094191875', '208043474854461068334845359605621664088125'],
            ['43282087429518774838368965030190873132158057030220864583445516227338925531094191876', '208043474854461068334845359605621664088126'],
            ['417927142998078702077756962900654390278806646288750249869048008644489266148390976576', '646472847842876307508176358984831821937976'],
            ['417927142998078702077756962900654390278807939234445935621663024997207235812034852528', '646472847842876307508176358984831821937976'],
            ['417927142998078702077756962900654390278807939234445935621663024997207235812034852529', '646472847842876307508176358984831821937977'],
            ['57289022864599235425935956369596297021969148801199969985249642058425056660901854081', '239351254152969157626674463098033150396991'],
            ['57289022864599235425935956369596297021969627503708275923564895407351252727202648063', '239351254152969157626674463098033150396991'],
            ['57289022864599235425935956369596297021969627503708275923564895407351252727202648064', '239351254152969157626674463098033150396992'],
            ['388204130919598896854532928319816854683262176908475545926963148518011088548336100', '19702896510909224609070086389295007347690'],
            ['388204130919598896854532928319816854683301582701497364376181288690789678563031480', '19702896510909224609070086389295007347690'],
            ['388204130919598896854532928319816854683301582701497364376181288690789678563031481', '19702896510909224609070086389295007347691'],
            ['17641213493582550358331598783756045983107635305125558424245083335221980342711574945924', '4200144461037328458609200843143798139299618'],
            ['17641213493582550358331598783756045983107643705414480498902000553623666630307853545160', '4200144461037328458609200843143798139299618'],
            ['17641213493582550358331598783756045983107643705414480498902000553623666630307853545161', '4200144461037328458609200843143798139299619'],
            ['10480850478210993041934376751763741537950365795671072307889437499081947766082767900625', '3237414165381221816392010300367379458321975'],
            ['10480850478210993041934376751763741537950372270499403070333070283102548500841684544575', '3237414165381221816392010300367379458321975'],
            ['10480850478210993041934376751763741537950372270499403070333070283102548500841684544576', '3237414165381221816392010300367379458321976'],
            ['18912401257760832817656385679644608795185415305426367246326490097108178277089565964900', '4348839070115245459938703154127996458785930'],
            ['18912401257760832817656385679644608795185424003104507476817409974514486533082483536760', '4348839070115245459938703154127996458785930'],
            ['18912401257760832817656385679644608795185424003104507476817409974514486533082483536761', '4348839070115245459938703154127996458785931'],
            ['4058130180100830389999063645300769577286055319083847709319631222394835048000563913616', '2014480126509276434961389427500807039909204'],
            ['4058130180100830389999063645300769577286059348044100727872501145173690049614643732024', '2014480126509276434961389427500807039909204'],
            ['4058130180100830389999063645300769577286059348044100727872501145173690049614643732025', '2014480126509276434961389427500807039909205'],
            ['33468184484233190391618513143907824051569626629768396502905028100863488827541016357225', '5785169356573166941812342062118994545501165'],
            ['33468184484233190391618513143907824051569638200107109649238911725547613065530107359555', '5785169356573166941812342062118994545501165'],
            ['33468184484233190391618513143907824051569638200107109649238911725547613065530107359556', '5785169356573166941812342062118994545501166'],
            ['1186174880095419715607318419843788127322568925641042677386667373975880777802263411393536', '34440889653076903576703209294470985379444256'],
            ['1186174880095419715607318419843788127322568994522821983540474527382299366744234170282048', '34440889653076903576703209294470985379444256'],
            ['1186174880095419715607318419843788127322568994522821983540474527382299366744234170282049', '34440889653076903576703209294470985379444257'],
            ['1869407664810972914760141576011747496493758522541997263089274909609818581502013012539601', '43236647242946265649799966945038223493750199'],
            ['1869407664810972914760141576011747496493758609015291748981806209209752471578460000039999', '43236647242946265649799966945038223493750199'],
            ['1869407664810972914760141576011747496493758609015291748981806209209752471578460000040000', '43236647242946265649799966945038223493750200'],
            ['4306248093986950964567669268958861794743230041121457387922204677053975232405051053143025', '65622009219369007099774314994059599547494695'],
            ['4306248093986950964567669268958861794743230172365475826660218876602605220524250148132415', '65622009219369007099774314994059599547494695'],
            ['4306248093986950964567669268958861794743230172365475826660218876602605220524250148132416', '65622009219369007099774314994059599547494696'],
            ['142958974763379486489798376541073126699563800627039429166077132824711226669748693162209', '11956545268737934506570289899105912433421297'],
            ['142958974763379486489798376541073126699563824540129966641946145965291024881573560004803', '11956545268737934506570289899105912433421297'],
            ['142958974763379486489798376541073126699563824540129966641946145965291024881573560004804', '11956545268737934506570289899105912433421298'],
            ['5924043074304444391210747851995027637083074459394928466831722415634829619104396893068176', '76967805440355673370818024381288105628528724'],
            ['5924043074304444391210747851995027637083074613330539347543069157270878381680608150125624', '76967805440355673370818024381288105628528724'],
            ['5924043074304444391210747851995027637083074613330539347543069157270878381680608150125625', '76967805440355673370818024381288105628528725'],
            ['692647042392925303434391597094843086706651167173473530228926147229989661906443247661192976', '832254193376594110047678950757920520714968676'],
            ['692647042392925303434391597094843086706651168837981916982114367325347563422284289091130328', '832254193376594110047678950757920520714968676'],
            ['692647042392925303434391597094843086706651168837981916982114367325347563422284289091130329', '832254193376594110047678950757920520714968677'],
            ['63418927703603396384251230391169377358870766926264966118026529953350707524741421056456161', '251831149192476577524577782662609731668640369'],
            ['63418927703603396384251230391169377358870767429927264502979685002506272849960884393736899', '251831149192476577524577782662609731668640369'],
            ['63418927703603396384251230391169377358870767429927264502979685002506272849960884393736900', '251831149192476577524577782662609731668640370'],
            ['84173934762470484045776658803140625724430904400114977656227228331290197573900111299728676', '290127445724237685478628941387635987912451526'],
            ['84173934762470484045776658803140625724430904980369869104702599288548080349172087124631728', '290127445724237685478628941387635987912451526'],
            ['84173934762470484045776658803140625724430904980369869104702599288548080349172087124631729', '290127445724237685478628941387635987912451527'],
            ['241361038321369463167942286580817659556660094711530196521279493146351719422610472610272609', '491285088641380874516359084346407133019022897'],
            ['241361038321369463167942286580817659556660095694100373804041242179069888115424738648318403', '491285088641380874516359084346407133019022897'],
            ['241361038321369463167942286580817659556660095694100373804041242179069888115424738648318404', '491285088641380874516359084346407133019022898'],
            ['517176462045529989620702018222975120090314182384304448758922583345467659488721121496429225', '719149818915036638988212861879727351601847565'],
            ['517176462045529989620702018222975120090314183822604086588995861321893383248175824700124355', '719149818915036638988212861879727351601847565'],
            ['517176462045529989620702018222975120090314183822604086588995861321893383248175824700124356', '719149818915036638988212861879727351601847566'],
            ['77611022182569680065221018033132479141166512436949302435927867888665269025756976722964990841', '8809711810415235147462139161930461303380344971'],
            ['77611022182569680065221018033132479141166512454568726056758338183589547349617899329725680783', '8809711810415235147462139161930461303380344971'],
            ['77611022182569680065221018033132479141166512454568726056758338183589547349617899329725680784', '8809711810415235147462139161930461303380344972'],
            ['6105408765553994361209114516342314667656879868595988990330444228342133209919341402497612900', '2470912537010161831197388767291893513920213770'],
            ['6105408765553994361209114516342314667656879873537814064350767890736910744503128430338040440', '2470912537010161831197388767291893513920213770'],
            ['6105408765553994361209114516342314667656879873537814064350767890736910744503128430338040441', '2470912537010161831197388767291893513920213771'],
            ['90701786360131891675282105453602007239665462813393298839266200083003999684896009231900541761', '9523748545616473366260116123335442806957838431'],
            ['90701786360131891675282105453602007239665462832440795930499146815524231931566894845816218623', '9523748545616473366260116123335442806957838431'],
            ['90701786360131891675282105453602007239665462832440795930499146815524231931566894845816218624', '9523748545616473366260116123335442806957838432'],
            ['65323751401133593272677920818977066639472955689716002480072016807758009911894964398074221636', '8082311018584572712389536725132408571687355694'],
            ['65323751401133593272677920818977066639472955705880624517241162232537083362159781541448933024', '8082311018584572712389536725132408571687355694'],
            ['65323751401133593272677920818977066639472955705880624517241162232537083362159781541448933025', '8082311018584572712389536725132408571687355695'],
            ['54888850556449896314370999310468417973690137670860368033970930193923337744682096321526909321', '7408701003310222419788126612344084182212588611'],
            ['54888850556449896314370999310468417973690137685677770040591375033499590969370264685952086543', '7408701003310222419788126612344084182212588611'],
            ['54888850556449896314370999310468417973690137685677770040591375033499590969370264685952086544', '7408701003310222419788126612344084182212588612'],
            ['2805085217054754253789444062542634634800093806651719072914037849606695181828139974525774321216', '52963055208841136620374558431841622364185138904'],
            ['2805085217054754253789444062542634634800093806757645183331720122847444298691823219254144599024', '52963055208841136620374558431841622364185138904'],
            ['2805085217054754253789444062542634634800093806757645183331720122847444298691823219254144599025', '52963055208841136620374558431841622364185138905'],
            ['1160568960830598642226220064123168954080961712013862945053127684556984147281594628487247009409', '34067124340492824200405891794016084650299718847'],
            ['1160568960830598642226220064123168954080961712081997193734113332957795930869626797787846447103', '34067124340492824200405891794016084650299718847'],
            ['1160568960830598642226220064123168954080961712081997193734113332957795930869626797787846447104', '34067124340492824200405891794016084650299718848'],
            ['124505361771365706389189800193135630498052202514496166178932088454657696893428061453489005584', '11158197066343904036990752059839856835208279172'],
            ['124505361771365706389189800193135630498052202536812560311619896528639201013107775123905563928', '11158197066343904036990752059839856835208279172'],
            ['124505361771365706389189800193135630498052202536812560311619896528639201013107775123905563929', '11158197066343904036990752059839856835208279173'],
            ['8304391676065346969717869193750447720841513935376871747395147291005945252957072474745223924900', '91128435057699454436963842029258931771642571930'],
            ['8304391676065346969717869193750447720841513935559128617510546199879872937015590338288509068760', '91128435057699454436963842029258931771642571930'],
            ['8304391676065346969717869193750447720841513935559128617510546199879872937015590338288509068761', '91128435057699454436963842029258931771642571931'],
            ['3902633675982472936031277376575750479219313797204897526330941750565757566042240677294833640976', '62471062708925265536995715391198772286875632324'],
            ['3902633675982472936031277376575750479219313797329839651748792281639748996824638221868584905624', '62471062708925265536995715391198772286875632324'],
            ['3902633675982472936031277376575750479219313797329839651748792281639748996824638221868584905625', '62471062708925265536995715391198772286875632325'],
            ['21803189113281615247645689642554433787955300436727779524987566434496560052640713833839771848809', '147659029907695165135263001196567750660989708947'],
            ['21803189113281615247645689642554433787955300437023097584802956764767086055033849335161751266703', '147659029907695165135263001196567750660989708947'],
            ['21803189113281615247645689642554433787955300437023097584802956764767086055033849335161751266704', '147659029907695165135263001196567750660989708948'],
            ['765773631185413773891853587035522811215936001201156164967993252197672361266093168217637840800625', '875084927984372139644261680745245536779196496025'],
            ['765773631185413773891853587035522811215936001202906334823961996476960884627583659291196233792675', '875084927984372139644261680745245536779196496025'],
            ['765773631185413773891853587035522811215936001202906334823961996476960884627583659291196233792676', '875084927984372139644261680745245536779196496026'],
            ['371531253548731146505174144638678761062942262073337722044484956271277190798021717224921126356624', '609533636109387441986227427959683991839011782068'],
            ['371531253548731146505174144638678761062942262074556789316703731155249645653941085208599149920760', '609533636109387441986227427959683991839011782068'],
            ['371531253548731146505174144638678761062942262074556789316703731155249645653941085208599149920761', '609533636109387441986227427959683991839011782069'],
            ['32639765347874160297815057000511241042881990523574135639577516269227137979270282014943162493161', '180664787238338617809927067472657959412705501869'],
            ['32639765347874160297815057000511241042881990523935465214054193504846992114215597933768573496899', '180664787238338617809927067472657959412705501869'],
            ['32639765347874160297815057000511241042881990523935465214054193504846992114215597933768573496900', '180664787238338617809927067472657959412705501870'],
            ['23312804018277899268530151932487478598118645419296370443140824162402663914524726804469078709025', '152685310420740538469311655525476411276716730095'],
            ['23312804018277899268530151932487478598118645419601741063982305239341287225575679627022512169215', '152685310420740538469311655525476411276716730095'],
            ['23312804018277899268530151932487478598118645419601741063982305239341287225575679627022512169216', '152685310420740538469311655525476411276716730096'],
            ['69351939896058034711498082880502238045919014432567876454734811966028586293551025867711349695480441', '8327781210866315423454045118194686369495352533771'],
            ['69351939896058034711498082880502238045919014432584532017156544596875494383787415240450340400547983', '8327781210866315423454045118194686369495352533771'],
            ['69351939896058034711498082880502238045919014432584532017156544596875494383787415240450340400547984', '8327781210866315423454045118194686369495352533772'],
            ['56907419683049340072215388152597682083224395922699571395667588185898365170014916921330096015050884', '7543700662344002371027341442602779762847104806622'],
            ['56907419683049340072215388152597682083224395922714658796992276190640419852900122480855790224664128', '7543700662344002371027341442602779762847104806622'],
            ['56907419683049340072215388152597682083224395922714658796992276190640419852900122480855790224664129', '7543700662344002371027341442602779762847104806623'],
            ['1163525248981521754155547339230838194099208908132096593234853200207208035365529397606592383400336', '1078668275690687843154790865870705804593431876844'],
            ['1163525248981521754155547339230838194099208908134253929786234575893517617097270809215779247154024', '1078668275690687843154790865870705804593431876844'],
            ['1163525248981521754155547339230838194099208908134253929786234575893517617097270809215779247154025', '1078668275690687843154790865870705804593431876845'],
            ['12630009931573082897907144855573901252532449094542217468986509301292277005832281659959934465254400', '3553872526072521203015975245855936611531238601120'],
            ['12630009931573082897907144855573901252532449094549325214038654343698308956323993533182996942456640', '3553872526072521203015975245855936611531238601120'],
            ['12630009931573082897907144855573901252532449094549325214038654343698308956323993533182996942456641', '3553872526072521203015975245855936611531238601121'],
            ['31255308963169979605103962253255036593975285646828038303401111783018980508885388211539730531736976', '5590644771685103192691707139464277920149086765676'],
            ['31255308963169979605103962253255036593975285646839219592944481989404363923164316767380028705268328', '5590644771685103192691707139464277920149086765676'],
            ['31255308963169979605103962253255036593975285646839219592944481989404363923164316767380028705268329', '5590644771685103192691707139464277920149086765677'],
            ['8628012985404686018689218847909668804260178206412486603741772756323555243760489710208140383228648196', '92887098056752133732654744820968248061511732728514'],
            ['8628012985404686018689218847909668804260178206412672377937886260591020553250131646704263406694105224', '92887098056752133732654744820968248061511732728514'],
            ['8628012985404686018689218847909668804260178206412672377937886260591020553250131646704263406694105225', '92887098056752133732654744820968248061511732728515'],
            ['9233511524782328316011754355906749687042442384057568340621550432564122836332981092655854023226646089', '96091162573788898435727389725088702432559537501283'],
            ['9233511524782328316011754355906749687042442384057760522946698010360994291112431270060719142301648655', '96091162573788898435727389725088702432559537501283'],
            ['9233511524782328316011754355906749687042442384057760522946698010360994291112431270060719142301648656', '96091162573788898435727389725088702432559537501284'],
            ['453765858005120177768073445581083997020624166557446581526841386118834645505297210629607793706393856', '21301780629917306933323409392321012226117710104816'],
            ['453765858005120177768073445581083997020624166557489185088101220732701292324081852654060029126603488', '21301780629917306933323409392321012226117710104816'],
            ['453765858005120177768073445581083997020624166557489185088101220732701292324081852654060029126603489', '21301780629917306933323409392321012226117710104817'],
            ['1371661154627246967424438399063581804666179749323634366508032096471110921410597239232159814550961124', '37035944089860149903963455856570016412483527052182'],
            ['1371661154627246967424438399063581804666179749323708438396211816770918848322310379264984781605065488', '37035944089860149903963455856570016412483527052182'],
            ['1371661154627246967424438399063581804666179749323708438396211816770918848322310379264984781605065489', '37035944089860149903963455856570016412483527052183'],
            ['370256269805896436402015708175373292783056837812420632207201736440955067457713055190230384049843049', '19242044325016415375787142063732125294673656555757'],
            ['370256269805896436402015708175373292783056837812459116295851769271706641741840519440819731362954563', '19242044325016415375787142063732125294673656555757'],
            ['370256269805896436402015708175373292783056837812459116295851769271706641741840519440819731362954564', '19242044325016415375787142063732125294673656555758'],
        ];
    }

    public function testSqrtOfNegativeNumber() : void
    {
        $number = BigInteger::of(-1);
        $this->expectException(NegativeNumberException::class);
        $number->sqrt();
    }

    /**
     * @param string $number   The number as a string.
     * @param string $expected The expected absolute result.
     */
    #[DataProvider('providerAbs')]
    public function testAbs(string $number, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($number)->abs());
    }

    public static function providerAbs() : array
    {
        return [
            ['0', '0'],
            ['123456789012345678901234567890', '123456789012345678901234567890'],
            ['-123456789012345678901234567890', '123456789012345678901234567890'],
        ];
    }

    /**
     * @param string $number   The number to negate as a string.
     * @param string $expected The expected negated result.
     */
    #[DataProvider('providerNegated')]
    public function testNegated(string $number, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($number)->negated());
    }

    public static function providerNegated() : array
    {
        return [
            ['0', '0'],
            ['123456789012345678901234567890', '-123456789012345678901234567890'],
            ['-123456789012345678901234567890', '123456789012345678901234567890'],
        ];
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The second operand as a string.
     * @param string $c The expected result.
     */
    #[DataProvider('providerOr')]
    public function testOr(string $a, string $b, string $c) : void
    {
        self::assertBigIntegerEquals($c, BigInteger::of($a)->or($b));
    }

    public static function providerOr() : array
    {
        return [
            ['-1', '-2', '-1'],
            ['1', '-2', '-1'],
            ['-1', '2', '-1'],
            ['0', '1', '1'],
            ['123456789', '2', '123456791'],
            ['123456789012345678901234567890', '5', '123456789012345678901234567895'],

            // +/+
            ['5394692623120841541359442506121617881401403431187', '9766511485175005460536', '5394692623120841541359442515868493073997685913915'],
            ['13019371322243819899204272493521124405791800889', '3726873297591390232697342938795296319136', '13019372133120279604421814277427131266071789241'],
            ['825328913860854183466591', '820522975067667161505095715672113', '820522975740678967606674026518143'],
            ['7315468442240573482143183', '5515620804916021008917193641105420503', '5515620804918462921325666985224175071'],
            ['110104465264058103889358298866', '94814454566057673772310777098551244', '94814554220241714731352195897180158'],
            ['582122067691582101691266880169169117', '55049524716805317255574656192191', '582165366513537784238545389955702527'],
            ['44873367566918874237638126', '9409284695426246790492523165', '9409370989367316235989229567'],
            ['64440906628912978316185121528516412439581546252', '81625941605259372046980969698971221793201400420812216', '81625958913430687266413542869832062998782644017813436'],
            ['61190858896401275408368039585731416556', '77528509846707811172472891497150', '61190890789450813957866744619030404094'],
            ['16370447908750281436283735', '33298946904845025350531059039688786', '33298946921165556207545256032852823'],

            // +/-
            ['629228300462038209944858167835301131122621385', '-24883098317384873963133964889006559124430650726905548810', '-24883098316846256827607757635214967359849628880391897089'],
            ['775691429999776584456355266541756753945050', '-44019102118444435028320990611417008365859', '-106341809525727913368330013670930268193'],
            ['23036338384780646740896760425349240', '-5478898094914207535986208043291517993', '-5457429153187938999914259991937295361'],
            ['71118708446231992585781812506893296427746901059', '-1538512593651669138154724', '-21301756145772576637089'],
            ['95761531088891927652144172762302643791728575066056439', '-73813927170349582735449', '-62002966777642928920585'],
            ['147250036603078668581525429762829', '-361868247492494445272460684932230002238896', '-361868247487223141650087293986311332349091'],
            ['99930782821428633178253476728280061597459150050275', '-753226818836998475961273398390981', '-22821458929931233658600654659589'],
            ['875039889838938241455632936393708', '-74753436502079272149039840217097829379635018', '-74753436502056413508822744073804860617008130'],
            ['44473056973336336084179028836', '-642876954476407938176346144700252080168226421757640', '-642876954476407938176342871835826873856675947762820'],
            ['10832413402209480147931243970039726', '-93114160710811589646365329687673290', '-92992446445992844023796182323654722'],

            // -/+
            ['-58282712640531681730833687888223654527698209637332654', '42073831540395345067530857', '-58282712640531681730833687848629557121142117902623877'],
            ['-1084275441589706957474009274660708408270082347887678', '9981430101411912941313315601191084553434386669517100', '-94107724785771989656363150071297242939909531223058'],
            ['-598777304188523652658494946327505334467783703319', '41796915801148574135327226262820013943232613155788027', '-552389486488495482683109572827418077709951314693'],
            ['-37924152083831463156', '11645291103528743935841065217614462310970547876719983680210', '-724275812307698722'],
            ['-85805203640767691526502704', '896583742459515926613645044246150', '-8089494556653456495974698'],
            ['-898723825644461589477861601', '966718006713828880001720445', '-271140206795741014665988225'],
            ['-4803554725846075946809233272342849380', '3789669181482167533795', '-4803554725846075847653480259198920449'],
            ['-8239281675053995153625856701645533046744884636738', '195355546009266013074942651597617574642246792', '-8239281494701588116574460878233761583302994167874'],
            ['-2461312127713272017388112641949224482917501911058334', '218828275523635383548866565214082261313061778728394264714492', '-964642997122122969610553507645301283121473429586690'],
            ['-15681959488652631674', '73904389130453115377', '-15564582155250048521'],

            // -/-
            ['-71023078211213600373008149300531297714178520062731', '-853926183433843136944713294041079955', '-736401078260378233748751535570947'],
            ['-27149910991078057927341430954047198256402084022', '-14438392862108994971173507', '-4004612905874255759343745'],
            ['-238860647199613977017', '-6113291898513098650624035878857504327842', '-5909070255575269537'],
            ['-687121238445111757141748', '-65287940446908065749910389869762916518', '-4725401435649813786786'],
            ['-51999011814383818848599396253370345902663465788294927', '-514426331389927533029', '-479687516219085193477'],
            ['-546583149661189506949', '-1886943709420620214803722', '-315919918989248627969'],
            ['-8636166193965613127594731284', '-8181172184899045690506766177618298431021363952049', '-3335591938529949537265574161'],
            ['-94490697244560460969748233110763906395003420647076515', '-723986053083944708227437327714382632737927996062236', '-206117762652633881638126746265326880615590132920835'],
            ['-7227021890466860512716899555631477118800822', '-9868452730291944167866341531306323590', '-8517968707344152068783650362697974406'],
            ['-886177102305364710710271549944886', '-619676933192435363578115557006420998424428632929783759', '-196486036766831552439772004779013'],
        ];
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The second operand as a string.
     * @param string $c The expected result.
     */
    #[DataProvider('providerAnd')]
    public function testAnd(string $a, string $b, string $c) : void
    {
        self::assertBigIntegerEquals($c, BigInteger::of($a)->and($b));
    }

    public static function providerAnd() : array
    {
        return [
            ['-1', '-2', '-2'],
            ['1', '-2', '0'],
            ['-1', '2', '2'],
            ['0', '1', '0'],
            ['123456789', '123456787', '123456785'],
            ['-255', '255', '1'],
            ['-65535', '65535', '1'],
            ['123456789012345678901234567890', '123456789012345678901234567880', '123456789012345678901234567872'],
            ['-123456789012345678', '123456789012345678', '2'],
            ['-123456789012345678901234567890', '123456789012345678901234567880', '8'],
            ['-123456789012345678901234567890', '123456789012345678901234567890', '2'],

            // +/+
            ['9540274336583084975562289722883526775521835932895037762835', '957696937540728677043370494807703921521803820', '956215995421898870832092642430941754382745600'],
            ['665165726101105766657686931651143608914582782', '4447574838980484593141693556584', '1901555884282179808129622475880'],
            ['790343628306512069923527702470983342686', '62298850543399391744839187137607', '20307169700729362621431664508998'],
            ['2129188417498745891659300713194793359728416786577409728257', '13131659959207622018684646910022554990063156546254428690', '12356306984463677619589352676276775643362583267126565376'],
            ['104310239039582621686', '6446718309080309833322276220218494992', '76135640055047897104'],
            ['74868295422704959401678579346171048', '867186067426411270613182153780877', '856945737422567889012791074907272'],
            ['6937657292522823097429594605267918559241308579775080', '216218931239976107136182640332044026', '3250891995280997169353922931081832'],
            ['7906079216913996792813', '4742451651607606791972951532992811', '230746713170494308649'],
            ['293688858487958770805571838692', '8677568912502877580987165764167283758651602410275293', '247781448091522189113454887108'],
            ['72233549478210553977593718216', '316088176499879291090502991526073942544850660245451', '12734829629065561014968001992'],

            // +/-
            ['2664177563192220439082233', '-497575612689987685525145701582641566', '2588024780851115490075744'],
            ['72302616794456840461076482624687', '-7920544542105677637241746439802629410560020574577414593', '51978658148206026594900129745967'],
            ['58011260275322692847', '-6123749825659279738219243668730082', '57939162772413645838'],
            ['466382812961846768942257', '-5749717770169572418738550644839', '466336614426915108887697'],
            ['60996924305709811365429943772752206951836061719477499', '-46952464788284959785687905299628715212736', '60996924305663053101630907874782654442194351887089728'],
            ['1271298968079615636311319759', '-7076044481917178328978459111269631067437408434807094275466', '9671425457443162481033286'],
            ['6654184611673948944936810250930287210552163122670575877', '-511128347138119909516013001952844', '6654184611673948944936485472331537539300894385725579524'],
            ['697102292326392768156034363', '-385816895302447426012908574281578202973', '77527105904576612288962595'],
            ['926786641478132770994056080463057595622227100625806678987087', '-4322855505705341813979014306030462108040', '926786641478132770989781249376882824272551951998480271052872'],
            ['927695357427113145993764093616313117', '-11115367682761386491887989227580455376084253492', '251836538845141304306589183586877452'],

            // -/+
            ['-848481030369881011367', '24459162697579073867788448115909', '24459162697494549701243157153857'],
            ['-34065802561923171904150684209458120004', '4019358484940611693618982930850498281249420456156830959485', '4019358484940611693617652381249270184290140195729437360700'],
            ['-98954587067690432154450092126188399456988744363401', '7655495624455877244961837619471607354199154315816566', '7558944991169524242286724530424642134143850575839862'],
            ['-868644810088092713567148601735963298182552679670410544', '82858149617006726392344736421399315', '8137732778129228141094120234418704'],
            ['-998663980250596578114821117767', '244259108311520229266439571373536844773689777602030922967', '244259108311520229266439570374874161412287861875548399761'],
            ['-1248259686272528973901614707528215891498219194', '655960081606886139163780', '3646115001589090238468'],
            ['-64227536611226610452440126782979674374285173975671002634662', '461341395556252503179771531031624891465951855470266', '85686368347052340500241906477444420549161344263706'],
            ['-59791551673394204346327697704196561177891877794', '645966555806444815602615', '605676378409436309569558'],
            ['-78238580533531033368', '4270126481201125120087', '4192178106247701282880'],
            ['-24246028315072417932912875604255655638242139918336845', '88698793933327787439447955672549045569403546851781349889', '88698791693808181914782599290752525843380500099683258369'],

            // -/-
            ['-46546491822650354506208692474', '-6018394977185170090061542472648023760', '-6018395021828699316799393168518602496'],
            ['-743152617251558809630108680999418964331728228384787849230837', '-486018928970295572717155093024800', '-743152617251558809630108681140478317458875041694533184895488'],
            ['-4866622764858529488867', '-9537607529046144869970624844191278832590222289361241071473', '-9537607529046144869970624844191278832725160271731278438387'],
            ['-730775514685831862862019', '-7625523418577177635605525193753328220200935764095710810', '-7625523418577177635605525193754058951651831078253557468'],
            ['-2495682281115246180115', '-4987103315776729653070191331703716090918578703674535', '-4987103315776729653070191331703794637216039900659639'],
            ['-5488584277887675655108118932595839273325330416629', '-35229300045153487757488412419836606669940355895', '-5523085971325721180807451421014175245560668484599'],
            ['-38381423696890807426981559614356456230', '-739182484535059269819467633096483064087898332967634653', '-739182484535059291210108909612231991208362490711672830'],
            ['-597890573360345150854843009177617022270269706803', '-356012815728552252318079858872451714748749323785869', '-356564984188142681001344514389876040872667286820543'],
            ['-23463750166518460186485', '-22463992818295711097263710726797562089902730407', '-22463992818295711097263732647933653555539476471'],
            ['-4720389698935017471106', '-5791089930750302614122639001626417276700', '-5791089930750302617254009841385790242716'],
        ];
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The second operand as a string.
     * @param string $c The expected result.
     */
    #[DataProvider('providerXor')]
    public function testXor(string $a, string $b, string $c) : void
    {
        self::assertBigIntegerEquals($c, BigInteger::of($a)->xor($b));
    }

    public static function providerXor() : array
    {
        return [
            ['-1', '-2', '1'],
            ['1', '-2', '-1'],
            ['-1', '2', '-3'],
            ['0', '1', '1'],
            ['-83', '173', '-256'],
            ['-18771', '46765', '-65536'],
            ['123456789', '123456787', '6'],
            ['123456789012345678901234567890', '123456789012345678901234567880', '26'],

            // +/+
            ['41936628898433583900377933376976456781382668201', '37438930022022297882355139442212242', '41936628898407843119376111192570891919127786043'],
            ['1396562930260915648913', '71641901675063559788952950744943756922652019', '71641901675063559788951904814355699342310626'],
            ['5516178102046463636096186694651', '875416336636949134319480209741982233245414152081922', '875416336636949134324272999228874694385703653452281'],
            ['44625554404673588038549694469410638788', '3986527263757904531356805078494718124130202', '3986567880798165849629123513197484710278238'],
            ['967806838288818008878944294178353817974', '76572892056138061819934', '967806838288817933560480392383696345448'],
            ['6549960491760248668106', '809972248704249942320679286902130876735', '809972248704249945215832367837518077685'],
            ['81768549689528295227985831635629126', '19817599042102705952700129285270428899034937', '19817599060488705106871948403348505771766655'],
            ['51061712813654731819732216707359355', '66692382670293033297443892866698492227594289069400209372', '66692382670293033297492356940600616297078672795457426855'],
            ['2911271926407417388841130496527454120', '5721716929167795329992995424101009933', '8625837926505651836919858326429320613'],
            ['250487960291521021073935796160338835400290220715961112221', '730963719206186822116330979615072911065377412384726', '250487661935153548798668340542160754252631389086710550859'],

            // +/-
            ['315703524235733840821539711472063687', '-66841807242064399137898074', '-315703524297114064979936049584581279'],
            ['9277446674792088663850794', '-468221831505587189524134735682478879', '-468221831507190080423662165290212405'],
            ['25248048793307023015625438880440428269421254894139', '-646545451763103268373', '-25248048793307023015625438879795613802044300074032'],
            ['335324370082663461840497094633353490278304', '-209930584737852772355949', '-335324370082663461668349481746878508148941'],
            ['47721338528112883273875118081320011400', '-514064818694561791796525208693394326424194951567386', '-514064818694603840468840657747837685067866677419666'],
            ['759328623799165232926167861149310373966385535771841296', '-646816520151325139126678463083014308430319243677936375445', '-647381293125620242400109462625361980733466505018954892677'],
            ['6166808730528886865831', '-426783710143127948270328759492197246436605', '-426783710143127948273690847578353176066908'],
            ['77498570398475696333538111110', '-5075673799000376918718707282635634085023746', '-5075673799000434355613569601920122251640456'],
            ['22047659110158133001284', '-74320913378607121210', '-22121381026900967571326'],
            ['5803364274065958314081776638016430551', '-4274229015423103292286737939046', '-5803360773562161501600470160092978099'],

            // -/+
            ['-219350939601758896616523678389329861570463', '63548322315279018507052841', '-219350939601758950342282130799881889728696'],
            ['-5175986557609229988', '4562807781300154604786933585136050', '-4562807781300159526169887167051538'],
            ['-862734998654636443371922824446042207357345563995275488288', '317608759822741357639924588123216895628880482154756290644', '-1173569382701121390549222300360179251785924943446632748108'],
            ['-291785474648537977564128489162469251745788136086106796831700', '5786510264217139518265184', '-291785474648537977564128489162469247244511035252603664196788'],
            ['-689232696320162959219689095', '318033535871428535707877013012325978', '-318033536478373294479670092014067933'],
            ['-548532601216922744036631801709795957', '464214222118222367096184046350871', '-548808886226572611762806251826112612'],
            ['-2525374855554185527470347476684259136', '44783634414672425382682444859739', '-2525335156275258990485054325291398757'],
            ['-99017229521698702235373057368702644375364516', '779282291714372043825783864', '-99017229521698702705157919912107724461115292'],
            ['-4710489498953681316141687031448503', '2560025673790695682768396746333763204153628465907446181227', '-2560025673790695682768397234261771887641640339447134170846'],
            ['-2183346293342208505308848673968702587335086', '3020876205758828292116264754395150520917505612717', '-3020878389102130545864960825956618914014864271873'],

            // -/-
            ['-996523196457170777603', '-404534123678048856609187747', '404533718099753040655471008'],
            ['-88920925457369076369503008991228685035480552065', '-861198439422198824907245698639167235', '88920925456889524433116972809069378460008541570'],
            ['-41959726569833028356682369416236828694329874', '-58667070061040418591471570920753703169815', '41901315231453792392158501110242583793800455'],
            ['-53546995034536014873392', '-727312663703395826234974991306249786667844', '727312663703395826276124357261659078733932'],
            ['-34044215077835894902263474480877183', '-6692590003291305319757462955544', '34050847838851223962321332801038441'],
            ['-84458180012548319475', '-713807707081754243251117003805750221625293885560131', '713807707081754243251117003805686518886730992598448'],
            ['-860280316690197636629434822868211047371', '-4686935694950655198346422360359494231305', '5190952310140759138856214711198540086978'],
            ['-142063436269923412083052305900605223034381146', '-21112042865389423222803894', '142063436269923412065283924632113712290606828'],
            ['-94892910549951327470288236693704214482009665', '-7883187498538181992212844876690954593546', '94889133411885079989953104317264033746983753'],
            ['-736572386776427694074897356960455256508397907579', '-4106376469421816415487320805727264950214313', '736574400416293759865465431760192928844635287762'],
        ];
    }

    #[DataProvider('providerNot')]
    public function testNot(string $number, string $expected) : void
    {
        self::assertBigIntegerEquals($expected, BigInteger::of($number)->not());
    }

    public static function providerNot() : array
    {
        return [
            ['-32769', '32768'],
            ['-32768', '32767'],
            ['-32767', '32766'],
            ['-129', '128'],
            ['-128', '127'],
            ['-127', '126'],
            ['-1', '0'],
            ['0', '-1'],
            ['1', '-2'],
            ['126', '-127'],
            ['127', '-128'],
            ['128', '-129'],
            ['32766', '-32767'],
            ['32767', '-32768'],
            ['32768', '-32769'],
            ['-384790918029180298023485788625683230', '384790918029180298023485788625683229'],
            ['1934938934728983957845827937928492839', '-1934938934728983957845827937928492840'],
            ['-4839798736875689781936287349837948739479871298187364', '4839798736875689781936287349837948739479871298187363'],
            ['38797812039284098329810374984757861464098238209830923', '-38797812039284098329810374984757861464098238209830924'],
        ];
    }

    /**
     * @param string $a The base number as a string.
     * @param int    $b The distance to shift.
     * @param string $c The expected shifted result.
     */
    #[DataProvider('providerShiftedLeft')]
    public function testShiftedLeft(string $a, int $b, string $c) : void
    {
        self::assertBigIntegerEquals($c, BigInteger::of($a)->shiftedLeft($b));
    }

    /**
     * @param string $a The base number as a string.
     * @param int    $b The distance to shift, negated.
     * @param string $c The expected shifted result.
     */
    #[DataProvider('providerShiftedLeft')]
    public function testShiftedRight(string $a, int $b, string $c) : void
    {
        self::assertBigIntegerEquals($c, BigInteger::of($a)->shiftedRight(- $b));
    }

    public static function providerShiftedLeft() : array
    {
        return [
            ['-1', 1, '-2'],
            ['0', 1, '0'],
            ['123456789', 1, '246913578'],
            ['123456789012345678901234567890', 5, '3950617248395061724839506172480'],

            ['-3', -1, '-2'],
            ['-5', -1, '-3'],
            ['0', -1, '0'],
            ['123456789', -1, '61728394'],
            ['123456789012345678901234567890', -5, '3858024656635802465663580246'],

            ['0', 1234, '0'],
            ['123456789012345678901234567890123456789', -1234, '0'],

            // positive numbers
            ['940284045728639319919',                                     -30, '875707758337'],
            ['42978362369910548649285004217387822572104484',              -29, '80053438190204181986460470068059531'],
            ['459345371944440232668194594123525547022502888486824664',    -28, '1711194857748002680645117886824628513390209110'],
            ['6040754049963507472881320692872216084477635',               -27, '45007124915447141773114507592262447'],
            ['4078667231774103305184642707',                              -26, '60776877876730312484'],
            ['49933972074091440160998613689415662078540305149310089516',  -25, '1488148333850247864752966573518981399492630515972'],
            ['7469704937412423665301806569774896261346003606',            -24, '445229109371448973733294401751452461561'],
            ['4991662329264086789092371305806367691268283251684722',      -23, '595052519948969696651979840493961297424827009'],
            ['24261999837317270532438045068264219175859948',              -22, '5784511527375524170979987399164252084'],
            ['30371555400148612343',                                      -21, '14482286167215'],
            ['5661131843547409961338457445416414214646503036114430',      -20, '5398876040980730019892175145546354498526099239'],
            ['8205785117713792771432053488594',                           -19, '15651293025424562018264872'],
            ['121092557102525568761309643138214893231215421503311254716', -18, '461931446466543459935415813973292897152768789304013'],
            ['176832410305963244194755884422808046154',                   -17, '1349124224136072114522978854544128'],
            ['9257459072334599354676845177097713467419426212966300860',   -16, '141257615239480580973462603410304465750418490798435'],
            ['358222782083114778901537224',                               -15, '10932091738376305508469'],
            ['3589719402585266626776181409564006983030',                  -14, '219099084630448402513194666111084410'],
            ['4053727526539426059356755247362494',                        -13, '494839785954519782636322662031'],
            ['725310076963322368834522210259051',                         -12, '177077655508623625203740773989'],
            ['15618902589710355419154884565473458',                       -11, '7626417280132009482009220979235'],
            ['4176145333272593876621060606107583696',                     -10, '4078266927024017457637754498151937'],
            ['36957174148569349375515',                                    -9, '72181980758924510499'],
            ['72983575681651773692689777',                                 -8, '285092092506452240987069'],
            ['9789916499422385005848170375683173495755783',                -7, '76483722651737382858188831060024792935592'],
            ['22219967481086668708627972044908677992130264215',            -6, '347186991891979198572312063201698093627035378'],
            ['476504808428399978450599528182547',                          -5, '14890775263387499326581235255704'],
            ['806897646899661595924',                                      -4, '50431102931228849745'],
            ['19227989434278291392821522776905972539189753171235',         -3, '2403498679284786424102690347113246567398719146404'],
            ['145495876920185147465944298588326187',                       -2, '36373969230046286866486074647081546'],
            ['43390213276209209707014978611315057',                        -1, '21695106638104604853507489305657528'],
            ['107341060399080801706832336473384922229279580',               0, '107341060399080801706832336473384922229279580'],
            ['4599979410301725512972129610968772187254123116327',           1, '9199958820603451025944259221937544374508246232654'],
            ['1668450699560464956',                                         2, '6673802798241859824'],
            ['97153745662292681364860198',                                  3, '777229965298341450918881584'],
            ['3009085333029300076464813411170930572730358705813',           4, '48145365328468801223437014578734889163685739293008'],
            ['1259873000065121436479893867676',                             5, '40315936002083885967356603765632'],
            ['5095511474364199858927072259281168',                          6, '326112734359308790971332624593994752'],
            ['8881118206295853395928412137856827361',                       7, '1136783130405869234678836753645673902208'],
            ['73610836099012694560370201022735572',                         8, '18844374041347249807454771461820306432'],
            ['7873342832620148736874355104307939702351545648205041367132',  9, '4031151530301516153279669813405665127603991371880981179971584'],
            ['80476295381045062126801719697',                              10, '82407726470190143617844960969728'],
            ['3897101494489818878437105205',                               11, '7981263860715149063039191459840'],
            ['71689619759395149437011083050942636989790',                  12, '293640682534482532093997396176661041110179840'],
            ['333645680629360116658609620408319342692139569341169',        13, '2733225415715718075667330010384952055334007352042856448'],
            ['890373515888093126475933575522120914024009528750',           14, '14587879684310517784181695701354429055369372119040000'],
            ['30946288676751711479181903524629432893',                     15, '1014047987359800081749832614695057257037824'],
            ['8574513284773012626093531873375657960',                      16, '561939302630884155463665704853547120066560'],
            ['69030243610586785879463086990006910709030034224',            17, '9047932090526831198792985737954185800453984645808128'],
            ['6865607235477540676116751515371066',                         18, '1799777743137024422999949709245432725504'],
            ['5735193503479456072418969854460250',                         19, '3006893131552237065296396867055255552000'],
            ['9048587571607615464851554762153259902641683626953182',       20, '9488131761486026993672183886279616655672406050816059768832'],
            ['2370160098545184898273',                                     21, '4970585990984231599783018496'],
            ['6371742038227378435605172332194796999298927822243',          22, '26725023117905246281972516733613965833347490160545103872'],
            ['26207519883779202355130662492597335498961566699008934586',   23, '219844610957229287109867916430701949345272990103839940739596288'],
            ['74820994561612168747902380445044412766494233082859272179',   24, '1255287987094992663312007783640686242576631311185475906949873664'],
            ['7863511842172307126057945',                                  25, '263855673389365411744426743562240'],
            ['90830051704668768299',                                       26, '6095501586961584536825102336'],
            ['231276168421338512977415195026',                             27, '31041361866057401930727182789066620928'],
            ['11591917961288582194173295725806158',                        28, '3111681783853090908886389181179626990338048'],
            ['38341121775077207775413588',                                 29, '20584233014488759408799784166752256'],
            ['891298514985607971246244019814562069431',                    30, '957024493209138036794881606984780018192056582144'],

            // negative numbers
            ['-1237372339423270864540664117814',                            -30, '-1152392792909658387807'],
            ['-35734786450376781153321',                                    -29, '-66561226640599'],
            ['-350375475884383289232681762242910646883',                    -28, '-1305250361131069396557963498842'],
            ['-4617840391045771047614500006075',                            -27, '-34405592017216764745225'],
            ['-27995582655724767116836664611604632973',                     -26, '-417166689868640409660885700757'],
            ['-88315321329400450504411498573284859753834938505',            -25, '-2632001678031696394217356996932174556072'],
            ['-437674836865187689637228663',                                -24, '-26087453178476553538'],
            ['-192317801462512108553659234',                                -23, '-22926068480314267702'],
            ['-126204812290953464957001472236330489664',                    -22, '-30089572022188535918474548396190'],
            ['-5028144589183389011262798573489176268405',                   -21, '-2397606176940626626616858755821789'],
            ['-60642321304451950418527643944555492427721664313816',         -20, '-57833024315311384600188869423442356517526307'],
            ['-5519655948197797131908202',                                  -19, '-10527908226390451683'],
            ['-441338568999382089736819258485250',                          -18, '-1683573032376793250033642802'],
            ['-15197228212774116306143991214042822478217336134849845725',   -17, '-115945649816697054337646417343466358018625916556167'],
            ['-86508083500205763872897394724129182619208043270959408954',   -16, '-1320008598330776426283224406801287576587036793074943'],
            ['-314559273451051294518231893383720551240487085598205396',     -15, '-9599587202485696243842526043204362525649630297797'],
            ['-1773964912051971854901509472944599559',                      -14, '-108274225589109610284515959042029'],
            ['-950863346236503075147803',                                   -13, '-116072185819885629291'],
            ['-123039115193432540987413574511885183658944',                 -12, '-30038846482771616452005267214815718667'],
            ['-44855253438053014529473887873121988016001',                  -11, '-21901979217799323500719671813047845711'],
            ['-969661032422144957997200094060422207789677054',              -10, '-946934601974750935544140716855881062294607'],
            ['-447489909594763600404330986711528177365046540847822',         -9, '-874003729677272657039708958420953471416106525094'],
            ['-14045183720116080215449840714517462781770864',                -8, '-54863998906703438341600940291083838991293'],
            ['-3299830436298183536285101988500049758',                       -7, '-25779925283579558877227359285156639'],
            ['-5019351831439253154367591191464319',                          -6, '-78427372366238330536993612366630'],
            ['-38795226574432330534560721103703978442243266604688184102678', -5, '-1212350830451010329205022534490749326320102081396505753209'],
            ['-550505672867291116049922196115226897669049024412835',         -4, '-34406604554205694753120137257201681104315564025803'],
            ['-4181785333295290828956638533933522764',                       -3, '-522723166661911353619579816741690346'],
            ['-58253383391148486554155255025355913',                         -2, '-14563345847787121638538813756338979'],
            ['-78072807980226950108001170178',                               -1, '-39036403990113475054000585089'],
            ['-2739739627183043192544967754527367420831889358990397380',      0, '-2739739627183043192544967754527367420831889358990397380'],
            ['-6802649114840946885444462648295302',                           1, '-13605298229681893770888925296590604'],
            ['-7711910168688261765675862240931391560',                        2, '-30847640674753047062703448963725566240'],
            ['-78150769840135288210477422',                                   3, '-625206158721082305683819376'],
            ['-48416578493609461712161279293681',                             4, '-774665255897751387394580468698896'],
            ['-963028038608902450630794252350953199258094761611',             5, '-30816897235484878420185416075230502376259032371552'],
            ['-1425323867965759220038394395246107977999821',                  6, '-91220727549808590082457241295750910591988544'],
            ['-224892690751744072093860005492753257',                         7, '-28786264416223241228014080703072416896'],
            ['-1342157478927956315396',                                       8, '-343592314605556816741376'],
            ['-5671022663122810048459617618469813436271952017702608325',      9, '-2903563603518878744811324220656544479371239433063735462400'],
            ['-46213908327252308063252283743615825103944376575531142630707', 10, '-47323042127106363456770338553462604906439041613343890053843968'],
            ['-1443335666407076242843975171397153215688292050',              11, '-2955951444801692145344461151021369785729622118400'],
            ['-387072173209607249289609530361384',                           12, '-1585447621466551293090240636360228864'],
            ['-30736899295720315680717',                                     13, '-251796679030540826056433664'],
            ['-66511372620325071045088',                                     14, '-1089722329011405964002721792'],
            ['-16831212979074223740',                                        15, '-551525186898304163512320'],
            ['-2867585884213675945325235733',                                16, '-187930108507827466752834648997888'],
            ['-847247216997347939369738040936389314911814493500001189',      17, '-111050387226276389109070304501614420284121349292032155844608'],
            ['-4089932815136020694242664',                                   18, '-1072151347891017008871548911616'],
            ['-9681942860006157944589342578524581427227754117530',           19, '-5076126458186908536452857241809495747318384750771568640'],
            ['-3145980648451124121924460760091441029335360706964602774838',  20, '-3298799804430285927271063365973642868776355188666115319228530688'],
            ['-19270031819736683617335308049302261495063213645341141',       21, '-40412185770824425521461975946210336298894808622754464530432'],
            ['-5854557994845898152102048100066000953660980958739',           22, '-24555796016014130002954228754299228063944067079162822656'],
            ['-537126409576534616665629846223812575792202734591383198404',   23, '-4505742896384994897638235853071843963791078197015153829197381632'],
            ['-207462793032740058202867058129136',                           24, '-3480648090673575028322072453517070565376'],
            ['-8403828351720334896765545419908214',                          25, '-281985686967472060310746513735221612904448'],
            ['-1998653332269111002668466897443760635558181763512849966686',  26, '-134127354658394581678981782109055280140227584054864010666735304704'],
            ['-939931592701732496693966621537',                              27, '-126155482847847917370031711250532007936'],
            ['-40818093627295495469409741857838451891730234',                28, '-10957023575893760373076938106451152007890667992776704'],
            ['-690188350197447300310251',                                    29, '-370542049022278912189502337318912'],
            ['-21087241977645058543789195285783471320391',                   30, '-22642253664205972385394994417649345764608320733184'],
        ];
    }

    /**
     * @param string $number    The number to test.
     * @param int    $bitLength The expected bit length.
     */
    #[DataProvider('providerGetBitLength')]
    public function testGetBitLength(string $number, int $bitLength) : void
    {
        self::assertSame($bitLength, BigInteger::of($number)->getBitLength());
    }

    public static function providerGetBitLength() : array
    {
        return [
            ['-10141204801825835211973625643009', 104],
            ['-10141204801825835211973625643008', 103],
            ['-5070602400912917605986812821505', 103],
            ['-5070602400912917605986812821504', 102],
            ['-2535301200456458802993406410753', 102],
            ['-2535301200456458802993406410752', 101],
            ['-1267650600228229401496703205377', 101],
            ['-1267650600228229401496703205376', 100],
            ['-633825300114114700748351602689', 100],
            ['-633825300114114700748351602688', 99],

            ['-18446744073709551617', 65],
            ['-18446744073709551616', 64],
            ['-9223372036854775809', 64],
            ['-9223372036854775808', 63],

            ['-4294967297', 33],
            ['-4294967296', 32],
            ['-2147483649', 32],
            ['-2147483648', 31],

            ['-9', 4],
            ['-8', 3],
            ['-7', 3],
            ['-6', 3],
            ['-5', 3],
            ['-4', 2],
            ['-3', 2],
            ['-2', 1],
            ['-1', 0],
            ['0', 0],
            ['1', 1],
            ['2', 2],
            ['3', 2],
            ['4', 3],
            ['5', 3],
            ['6', 3],
            ['7', 3],
            ['8', 4],

            ['2147483647', 31],
            ['2147483648', 32],
            ['4294967295', 32],
            ['4294967296', 33],

            ['9223372036854775807', 63],
            ['9223372036854775808', 64],
            ['18446744073709551615', 64],
            ['18446744073709551616', 65],

            ['633825300114114700748351602687', 99],
            ['633825300114114700748351602688', 100],
            ['1267650600228229401496703205375', 100],
            ['1267650600228229401496703205376', 101],
            ['2535301200456458802993406410751', 101],
            ['2535301200456458802993406410752', 102],
            ['5070602400912917605986812821503', 102],
            ['5070602400912917605986812821504', 103],
            ['10141204801825835211973625643007', 103],
            ['10141204801825835211973625643008', 104],
        ];
    }

    /**
     * @param string $number       The number to test.
     * @param int    $lowestSetBit The expected lowest set bit.
     */
    #[DataProvider('providerGetLowestSetBit')]
    public function testGetLowestSetBit(string $number, int $lowestSetBit) : void
    {
        self::assertSame($lowestSetBit, BigInteger::of($number)->getLowestSetBit());
    }

    public static function providerGetLowestSetBit() : array
    {
        return [
            ['-10', 1],
            ['-9', 0],
            ['-8', 3],
            ['-7', 0],
            ['-6', 1],
            ['-5', 0],
            ['-4', 2],
            ['-3', 0],
            ['-2', 1],
            ['-1', 0],
            ['0', -1],
            ['1', 0],
            ['2', 1],
            ['3', 0],
            ['4', 2],
            ['5', 0],
            ['6', 1],
            ['7', 0],
            ['8', 3],
            ['9', 0],
            ['10', 1],

            ['-1328165573307087716352', 67],
            ['-1254378597012249509888', 66],
            ['-1291272085159668613120', 65],

            ['1291272085159668613120', 65],
            ['1254378597012249509888', 66],
            ['1328165573307087716352', 67],
        ];
    }

    /**
     * @param string $number The number to test.
     * @param bool   $isOdd  Whether the number is even.
     */
    #[DataProvider('providerIsOdd')]
    public function testIsEven(string $number, bool $isOdd) : void
    {
        self::assertSame(! $isOdd, BigInteger::of($number)->isEven());
    }

    /**
     * @param string $number The number to test.
     * @param bool   $isOdd  Whether the number is even.
     */
    #[DataProvider('providerIsOdd')]
    public function testIsOdd(string $number, bool $isOdd) : void
    {
        self::assertSame($isOdd, BigInteger::of($number)->isOdd());
    }

    public static function providerIsOdd() : \Generator
    {
        $tests = [
            ['123456789012345678900', false],
            ['123456789012345678901', true],
            ['123456789012345678902', false],
            ['123456789012345678903', true],
            ['123456789012345678904', false],
            ['123456789012345678905', true],
            ['123456789012345678906', false],
            ['123456789012345678907', true],
            ['123456789012345678908', false],
            ['123456789012345678909', true],
        ];

        foreach ($tests as [$number, $isOdd]) {
            yield [$number, $isOdd];
            yield ['-' . $number, $isOdd];
        }
    }

    /**
     * @param BigInteger $number   The number in base 2.
     * @param int        $n        The bit to test.
     * @param bool       $expected The expected result.
     */
    #[DataProvider('providerTestBit')]
    public function testTestBit(BigInteger $number, int $n, bool $expected) : void
    {
        self::assertSame($expected, $number->testBit($n));
    }

    public static function providerTestBit() : \Generator
    {
        $base2BitsSetTests = [
            ['0', []],
            ['1', [0]],
            ['10', [1]],
            ['11', [0, 1]],
            ['100', [2]],
            ['101', [0, 2]],
            ['110', [1, 2]],
            ['111', [0, 1, 2]],
            ['100000010000000001000000000111', [0, 1, 2, 12, 22, 29]],
            ['101000010000000000001000100001000001000000100000100000000100000000001', [0, 11, 20, 26, 33, 39, 44, 48, 61, 66, 68]],
        ];

        foreach ($base2BitsSetTests as [$number, $bitsSet]) {
            $number = BigInteger::fromBase($number, 2);

            // test up to 5 bits after the last bit set
            $testBitCount = ($bitsSet[count($bitsSet) - 1] ?? 0) + 5;

            for ($n = 0; $n < $testBitCount; $n++) {
                $isSet = in_array($n, $bitsSet);
                yield [$number, $n, $isSet];
            }
        }

        $base10BitsUnsetTests = [
            [-1, []],
            [-2, [0]],
            [-3, [1]],
            [-4, [0, 1]],
            [-5, [2]],
            [-6, [0, 2]],
            [-7, [1, 2]],
            [-8, [0, 1, 2]],
            [-9, [3]],
            ['-1181745669222511412225', [10, 20, 30, 40, 50, 60, 70]],
        ];

        foreach ($base10BitsUnsetTests as [$number, $bitsUnset]) {
            $number = BigInteger::of($number);

            // test up to 5 bits after the last bit set
            $testBitCount = ($bitsUnset[count($bitsUnset) - 1] ?? 0) + 5;

            for ($n = 0; $n < $testBitCount; $n++) {
                $isSet = ! in_array($n, $bitsUnset);
                yield [$number, $n, $isSet];
            }
        }
    }

    public function testTestNegativeBitThrowsException() : void
    {
        $number = BigInteger::one();

        $this->expectException(\InvalidArgumentException::class);
        $number->testBit(-1);
    }

    #[DataProvider('providerModInverse')]
    public function testModInverse(string $x, string $m, string $expectedResult) : void
    {
        $x = BigInteger::of($x);
        $m = BigInteger::of($m);

        self::assertSame($expectedResult, (string) $x->modInverse($m));
    }

    public static function providerModInverse() : array
    {
        return [
            ['1', '1', '0'],
            ['1234567890', '19', '11'],
            ['-1234567890', '19', '8'],
            ['1234567890', '1000000001', '189108911'],
            ['19', '1234567890', '519818059'],
            ['1000000001', '1234567890', '1001100101'],
            ['12345', '12346', '12345'],
            ['1234567890', '137', '42'],
            ['137', '1234567890', '856087223'],
            ['1234567890123456', '137', '77'],
            ['137', '1234567890123456', '540686667207353'],
            ['590295810358705600000', '137', '128'],
            ['18506109802501380149367860917982816833935316655779336003703143134999470532428', '115792089237316195423570985008687907853269984665640564039457584007908834671663', '95929095851002583825372225918533539673793386278360575987103577151530201707061'],
            ['-18506109802501380149367860917982816833935316655779336003703143134999470532428', '115792089237316195423570985008687907853269984665640564039457584007908834671663', '19862993386313611598198759090154368179476598387279988052354006856378632964602'],
        ];
    }

    #[DataProvider('providerModInverseThrows')]
    public function testModInverseThrows(string $x, string $m, string $expectedException) : void
    {
        $x = BigInteger::of($x);
        $m = BigInteger::of($m);

        $this->expectException($expectedException);
        $x->modInverse($m);
    }

    public static function providerModInverseThrows() : array
    {
        return [
            ['0', '0', DivisionByZeroException::class],
            ['1', '0', DivisionByZeroException::class],
            ['-1234567890', '-19', NegativeNumberException::class],
            ['0', '1000000001', MathException::class],
            ['2', '4', MathException::class],
            ['99', '9', MathException::class],
            ['19', '1000000001', MathException::class],
            ['123456789012345678901234567890', '123456789012345678901234567899', MathException::class],
        ];
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The expected comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testCompareTo(string $a, string $b, int $c) : void
    {
        self::assertSame($c, BigInteger::of($a)->compareTo($b));
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsEqualTo(string $a, string $b, int $c) : void
    {
        self::assertSame($c === 0, BigInteger::of($a)->isEqualTo($b));
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThan(string $a, string $b, int $c) : void
    {
        self::assertSame($c < 0, BigInteger::of($a)->isLessThan($b));
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsLessThanOrEqualTo(string $a, string $b, int $c) : void
    {
        self::assertSame($c <= 0, BigInteger::of($a)->isLessThanOrEqualTo($b));
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThan(string $a, string $b, int $c) : void
    {
        self::assertSame($c > 0, BigInteger::of($a)->isGreaterThan($b));
    }

    /**
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The comparison result.
     */
    #[DataProvider('providerCompareTo')]
    public function testIsGreaterThanOrEqualTo(string $a, string $b, int $c) : void
    {
        self::assertSame($c >= 0, BigInteger::of($a)->isGreaterThanOrEqualTo($b));
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

            [ '9999999999999999999999999',  '11111111111111111111111111111111111111111111', -1],
            [ '9999999999999999999999999', '-11111111111111111111111111111111111111111111',  1],
            ['-9999999999999999999999999',  '11111111111111111111111111111111111111111111', -1],
            ['-9999999999999999999999999', '-11111111111111111111111111111111111111111111',  1],

            [ '11111111111111111111111111111111111111111111', '9999999999999999999999999',  1],
            [ '11111111111111111111111111111111111111111111', '-9999999999999999999999999', 1],
            ['-11111111111111111111111111111111111111111111', '9999999999999999999999999', -1],
            ['-11111111111111111111111111111111111111111111','-9999999999999999999999999', -1],

            ['123', '123.000000000000000000000000000000000000000000000000000000001', -1],
            ['123', '123.000000000000000000000000000000000000000000000000000000000', 0],
            ['123', '122.999999999999999999999999999999999999999999999999999999999', 1],

            ['123', '246/2', 0],
            ['123', '245/2', 1],
            ['123', '247/2', -1],
        ];
    }

    /**
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testGetSign(int|string $number, int $sign) : void
    {
        self::assertSame($sign, BigInteger::of($number)->getSign());
    }

    /**
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsZero(int|string $number, int $sign) : void
    {
        self::assertSame($sign === 0, BigInteger::of($number)->isZero());
    }

    /**
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegative(int|string $number, int $sign) : void
    {
        self::assertSame($sign < 0, BigInteger::of($number)->isNegative());
    }

    /**
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsNegativeOrZero(int|string $number, int $sign) : void
    {
        self::assertSame($sign <= 0, BigInteger::of($number)->isNegativeOrZero());
    }

    /**
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositive(int|string $number, int $sign) : void
    {
        self::assertSame($sign > 0, BigInteger::of($number)->isPositive());
    }

    /**
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    #[DataProvider('providerSign')]
    public function testIsPositiveOrZero(int|string $number, int $sign) : void
    {
        self::assertSame($sign >= 0, BigInteger::of($number)->isPositiveOrZero());
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

            [ '1000000000000000000000000000000000000000000000000000000000000000000000000000000000', 1],
            ['-1000000000000000000000000000000000000000000000000000000000000000000000000000000000', -1]
        ];
    }

    /**
     * @param string $number
     * @param int    $scale
     * @param string $expected
     */
    #[DataProvider('providerToScale')]
    public function testToScale(string $number, int $scale, string $expected) : void
    {
        self::assertBigDecimalEquals($expected, BigInteger::of($number)->toScale($scale));
    }

    public static function providerToScale() : array
    {
        return [
            ['12345678901234567890123456789', 0, '12345678901234567890123456789'],
            ['12345678901234567890123456789', 1, '12345678901234567890123456789.0'],
            ['12345678901234567890123456789', 2, '12345678901234567890123456789.00'],
        ];
    }

    #[DataProvider('providerToInt')]
    public function testToInt(int $number) : void
    {
        self::assertSame($number, BigInteger::of((string) $number)->toInt());
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

    public function testToIntNegativeOverflowThrowsException() : void
    {
        $this->expectException(IntegerOverflowException::class);
        BigInteger::of(PHP_INT_MIN)->minus(1)->toInt();
    }

    public function testToIntPositiveOverflowThrowsException() : void
    {
        $this->expectException(IntegerOverflowException::class);
        BigInteger::of(PHP_INT_MAX)->plus(1)->toInt();
    }

    /**
     * @param string $value The big integer value.
     * @param float  $float The expected float value.
     */
    #[DataProvider('providerToFloat')]
    public function testToFloat(string $value, float $float) : void
    {
        self::assertSame($float, BigInteger::of($value)->toFloat());
    }

    public static function providerToFloat() : array
    {
        return [
            ['0', 0.0],
            ['1', 1.0],
            ['-1', -1.0],
            ['100000000000000000000000000000000000000000000000000', 1e50],
            ['-100000000000000000000000000000000000000000000000000', -1e50],
            ['1e3000', INF],
            ['-1e3000', -INF],
        ];
    }

    /**
     * @param string $number   The number to convert, in base 10.
     * @param int    $base     The base to convert the number to.
     * @param string $expected The expected result.
     */
    #[DataProvider('providerToBase')]
    public function testToBase(string $number, int $base, string $expected) : void
    {
        self::assertSame($expected, BigInteger::of($number)->toBase($base));
    }

    public static function providerToBase() : \Generator
    {
        $tests = [
            ['640998479760579495168036691627608949', 36, '110011001100110011001111'],
            ['335582856048758779730579523833856636', 35, '110011001100110011001111'],
            ['172426711023004493064981145981549295', 34, '110011001100110011001111'],
            ['86853227285668653965326574185738990',  33, '110011001100110011001111'],
            ['42836489934972583913564073319498785',  32, '110011001100110011001111'],
            ['20658924711984480538771889603666144',  31, '110011001100110011001111'],
            ['9728140488839986222205212599027931',   30, '110011001100110011001111'],
            ['4465579470019956787945275674107410',   29, '110011001100110011001111'],
            ['1994689924537781753408144504465645',   28, '110011001100110011001111'],
            ['865289950909412968716094193925700',    27, '110011001100110011001111'],
            ['363729369583879309352831568000039',    26, '110011001100110011001111'],
            ['147793267388865354156500488297526',    25, '110011001100110011001111'],
            ['57888012016107577099138793486425',     24, '110011001100110011001111'],
            ['21788392294523974761749372677800',     23, '110011001100110011001111'],
            ['7852874701996329566765721637715',      22, '110011001100110011001111'],
            ['2699289081943123258094476428634',      21, '110011001100110011001111'],
            ['880809345058406615041344008421',       20, '110011001100110011001111'],
            ['271401690926468032718781859340',       19, '110011001100110011001111'],
            ['78478889737009209699633503455',        18, '110011001100110011001111'],
            ['21142384915931646646976872830',        17, '110011001100110011001111'],
            ['5261325448418072742917574929',         16, '110011001100110011001111'],
            ['1197116069565850925807253616',         15, '110011001100110011001111'],
            ['245991074299834917455374155',          14, '110011001100110011001111'],
            ['44967318722190498361960610',           13, '110011001100110011001111'],
            ['7177144825886069940574045',            12, '110011001100110011001111'],
            ['976899716207148313491924',             11, '110011001100110011001111'],
            ['110011001100110011001111',             10, '110011001100110011001111'],
            ['9849210196991880028870',                9, '110011001100110011001111'],
            ['664244955832213832265',                 8, '110011001100110011001111'],
            ['31291601125492514360',                  7, '110011001100110011001111'],
            ['922063395565287619',                    6, '110011001100110011001111'],
            ['14328039609468906',                     5, '110011001100110011001111'],
            ['88305875046485',                        4, '110011001100110011001111'],
            ['127093291420',                          3, '110011001100110011001111'],
            ['13421775',                              2, '110011001100110011001111'],

            ['106300512100105327644605138221229898724869759421181854980', 36, 'zyxwvutsrqponmlkjihgfedcba9876543210'],
            ['1101553773143634726491620528194292510495517905608180485',   35,  'yxwvutsrqponmlkjihgfedcba9876543210'],
            ['11745843093701610854378775891116314824081102660800418',     34,   'xwvutsrqponmlkjihgfedcba9876543210'],
            ['128983956064237823710866404905431464703849549412368',       33,    'wvutsrqponmlkjihgfedcba9876543210'],
            ['1459980823972598128486511383358617792788444579872',         32,     'vutsrqponmlkjihgfedcba9876543210'],
            ['17050208381689099029767742314582582184093573615',           31,      'utsrqponmlkjihgfedcba9876543210'],
            ['205646315052919334126040428061831153388822830',             30,       'tsrqponmlkjihgfedcba9876543210'],
            ['2564411043271974895869785066497940850811934',               29,        'srqponmlkjihgfedcba9876543210'],
            ['33100056003358651440264672384704297711484',                 28,         'rqponmlkjihgfedcba9876543210'],
            ['442770531899482980347734468443677777577',                   27,          'qponmlkjihgfedcba9876543210'],
            ['6146269788878825859099399609538763450',                     26,           'ponmlkjihgfedcba9876543210'],
            ['88663644327703473714387251271141900',                       25,            'onmlkjihgfedcba9876543210'],
            ['1331214537196502869015340298036888',                        24,             'nmlkjihgfedcba9876543210'],
            ['20837326537038308910317109288851',                          23,              'mlkjihgfedcba9876543210'],
            ['340653664490377789692799452102',                            22,               'lkjihgfedcba9876543210'],
            ['5827980550840017565077671610',                              21,                'kjihgfedcba9876543210'],
            ['104567135734072022160664820',                               20,                 'jihgfedcba9876543210'],
            ['1972313422155189164466189',                                 19,                  'ihgfedcba9876543210'],
            ['39210261334551566857170',                                   18,                   'hgfedcba9876543210'],
            ['824008854613343261192',                                     17,                    'gfedcba9876543210'],
            ['18364758544493064720',                                      16,                     'fedcba9876543210'],
            ['435659737878916215',                                        15,                      'edcba9876543210'],
            ['11046255305880158',                                         14,                       'dcba9876543210'],
            ['300771807240918',                                           13,                        'cba9876543210'],
            ['8842413667692',                                             12,                         'ba9876543210'],
            ['282458553905',                                              11,                          'a9876543210'],
            ['9876543210',                                                10,                           '9876543210'],
            ['381367044',                                                  9,                            '876543210'],
            ['16434824',                                                   8,                             '76543210'],
            ['800667',                                                     7,                              '6543210'],
            ['44790',                                                      6,                               '543210'],
            ['2930',                                                       5,                                '43210'],
            ['228',                                                        4,                                 '3210'],
            ['21',                                                         3,                                  '210'],
            ['2',                                                          2,                                   '10'],

            ['1', 2, '1'],
            ['0', 2, '0'],

            ['1', 8, '1'],
            ['0', 8, '0'],
        ];

        foreach ($tests as [$number, $base, $expected]) {
            yield [$number, $base, $expected];

            if ($number[0] !== '0') {
                yield ['-' . $number, $base, '-' . $expected];
            }
        }
    }

    #[DataProvider('providerToInvalidBaseThrowsException')]
    public function testToInvalidBaseThrowsException(int $base) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::of(0)->toBase($base);
    }

    public static function providerToInvalidBaseThrowsException() : array
    {
        return [
            [-2],
            [-1],
            [0],
            [1],
            [37]
        ];
    }

    #[DataProvider('providerFromArbitraryBase')]
    public function testFromArbitraryBase(string $base10, string $alphabet, string $baseN) : void
    {
        $number = BigInteger::fromArbitraryBase($baseN, $alphabet);

        self::assertBigIntegerEquals($base10, $number);
    }

    public static function providerFromArbitraryBase() : \Generator
    {
        foreach (self::providerArbitraryBase() as [$base10, $alphabet, $baseN]) {
            yield [$base10, $alphabet, $baseN];

            // test with a number of leading "zeros"
            yield [$base10, $alphabet, $alphabet[0] . $baseN];
            yield [$base10, $alphabet, $alphabet[0] . $alphabet[0] . $baseN];
        }
    }

    #[DataProvider('providerArbitraryBase')]
    public function testToArbitraryBase(string $base10, string $alphabet, string $baseN) : void
    {
        $base10 = BigInteger::of($base10);
        $actual = $base10->toArbitraryBase($alphabet);

        self::assertSame($baseN, $actual);
    }

    public static function providerArbitraryBase() : array
    {
        $base7  = '0123456';
        $base8  = '01234567';
        $base9  = '012345678';
        $base10 = '0123456789';
        $base11 = '0123456789A';
        $base12 = '0123456789AB';
        $base13 = '0123456789ABC';
        $base14 = '0123456789ABCD';
        $base15 = '0123456789ABCDE';
        $base16 = '0123456789ABCDEF';
        $base17 = '0123456789ABCDEFG';
        $base64 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz+/';
        $base72 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~_!$()+,;@';
        $base85 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#';
        $base95 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~_!$()+,;@.:=^*?&<>[]{}%#|`/\ "\'-';

        $base62LowerUpper = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base62UpperLower = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return [
            ['0', 'XY', 'X'],
            ['1', 'XY', 'Y'],
            ['2', 'XY', 'YX'],
            ['3', 'XY', 'YY'],
            ['4', 'XY', 'YXX'],

            ['1234567890', '9876543210', '8765432109'],
            ['9876543210', '1234567890', '0987654321'],

            ['98719827932647929837981791821991234', '01234567', '460150331736165026742535432255203706502'],
            ['98719827932647929837981791821991234', 'ABCDEFGH', 'EGABFADDBHDGBGFACGHECFDFEDCCFFCADHAGFAC'],

            ['994495526373444232246567036253784322009', $base7, '12202520340634022241654466246440466210615152466'],
            ['994495526373444232246567036253784322009', $base8, '13541315742261267512021577112421152053227731'],
            ['994495526373444232246567036253784322009', $base9, '66488066070032874134652704428716607733277'],
            ['994495526373444232246567036253784322009', $base10, '994495526373444232246567036253784322009'],
            ['994495526373444232246567036253784322009', $base11, '2A1978399765A213135A156506809522356825'],
            ['994495526373444232246567036253784322009', $base12, '14A05B751367AA17A09769472516764A47821'],
            ['994495526373444232246567036253784322009', $base13, '103A050CB893910A25357BB9C395A51C0814'],
            ['994495526373444232246567036253784322009', $base14, '10D925D22C52737225B8D5644D989CD666D'],
            ['994495526373444232246567036253784322009', $base15, '180B5C6CC477E8D58EAC276D06C5127124'],
            ['994495526373444232246567036253784322009', $base16, '2EC2CDF12C56F4A08DFC9511350AD2FD9'],
            ['994495526373444232246567036253784322009', $base17, '7266E944CF4A3786D0G7661356FG769G'],

            ['994495526373444232246567036253784322009', $base62LowerUpper, 'mLMLxPbmO0SM6PXtWChlWx'],
            ['994495526373444232246567036253784322009', $base62UpperLower, 'MlmlXpBMo0sm6pxTwcHLwX'],

            ['8149613250471589625', $base64, '74PFXAZBFRv'],
            ['454064679874654562007441356949657', $base64, '1PZ6Xm9ayTgCZU5xGYP'],
            ['45422310646719874654562007441356949657', $base64, 'YB0JHWGUe4+J+zbWTxGYP'],
            ['1121921454223110646719874654562007441356949657', $base64, 'oJmRKAU1GNNBHSz/S2Q0TxGYP'],
            ['10121192145422311064671918746545620075441356949657', $base64, '1kpPyynJk/pgMxgIopD9BB+eAaYP'],

            ['91906824217328753670', $base72, 'OdYuzDmu@os'],
            ['535903357336880946855837144765', $base72, '11;QwdMB!D)84;w@,'],
            ['3628645428648421468982810963905568210330', $base72, '3g_D_hpFwvT+jM2UiUF$eQ'],
            ['67461606287909524242401421486420908853942741199316', $base72, 'YdSH9KcqwE)dahLuF(uO,s2Y8Di'],
            ['673058295257771060991298040835276179059500055157907555831688', $base72, '2Y9hTIpkORoK;$uQNC!8u$1~9RBE1QRVG'],

            ['79248970614563033069', $base85, '42dhgJI>!D{'],
            ['70259972284912331680149126100', $base85, '!vcNSNE+R.X.t$k'],
            ['1345211446421580809283013645361855592276', $base85, '3E0H]t@k=%[$4EHpk/WV6'],
            ['92817563463558871408829910215554937029176299613741', $base85, 'R%GwIo]>peBh?fLxaPWsYp%I16'],
            ['105332456216236666737534759570691835270616864952881377663761', $base85, 'd!qFsWcY1Q6IuAU{50jN6?nK=lFms11'],

            ['82164606170768213165', $base95, '1ZY-^xBX,-A'],
            ['524820792661006993039498194693', $base95, '1Cwv({YtIbrPpE]r'],
            ['2500692630577003661291596854860146627030', $base95, '(Pszub<V3^Y]cs\YnU}o'],
            ['76088698829341245347114640636745832062447993955533', $base95, '2;t?i8zv}hWZ> )loCj(d7*yO3'],
            ['949872477171550708823123033931693463913459064733934993892215', $base95, '4ed~yPcS~L3d)w}!A!%R5_4Dx9u;B?0'],
        ];
    }

    #[DataProvider('providerArbitraryBaseWithInvalidAlphabet')]
    public function testFromArbitraryBaseWithInvalidAlphabet(string $alphabet) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The alphabet must contain at least 2 chars.');

        BigInteger::fromArbitraryBase('0', $alphabet);
    }

    #[DataProvider('providerFromArbitraryBaseWithInvalidNumber')]
    public function testFromArbitraryBaseWithInvalidNumber(string $number, string $alphabet, string $expectedMessage) : void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessage($expectedMessage);

        BigInteger::fromArbitraryBase($number, $alphabet);
    }

    public static function providerFromArbitraryBaseWithInvalidNumber() : array
    {
        return [
            ['', '01', 'The number cannot be empty.'],
            ['X', '01', 'Char "X" is not a valid character in the given alphabet.'],
            ['1', 'XY', 'Char "1" is not a valid character in the given alphabet.'],
            [' ', 'XY', 'Char " " is not a valid character in the given alphabet.'],

            ["\x00", '01', 'Char 00 is not a valid character in the given alphabet.'],
            ["\x1F", '01', 'Char 1F is not a valid character in the given alphabet.'],
            ["\x7F", '01', 'Char 7F is not a valid character in the given alphabet.'],
            ["\x80", '01', 'Char 80 is not a valid character in the given alphabet.'],
            ["\xFF", '01', 'Char FF is not a valid character in the given alphabet.'],
        ];
    }

    #[DataProvider('providerArbitraryBaseWithInvalidAlphabet')]
    public function testToArbitraryBaseWithInvalidAlphabet(string $alphabet) : void
    {
        $number = BigInteger::of(123);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The alphabet must contain at least 2 chars.');

        $number->toArbitraryBase($alphabet);
    }

    public static function providerArbitraryBaseWithInvalidAlphabet() : array
    {
        return [
            [''],
            ['0']
        ];
    }

    public function testToArbitraryBaseOnNegativeNumber() : void
    {
        $number = BigInteger::of(-123);

        $this->expectException(NegativeNumberException::class);
        $this->expectExceptionMessage('toArbitraryBase() does not support negative numbers.');

        $number->toArbitraryBase('01');
    }

    #[DataProvider('providerFromBytes')]
    public function testFromBytes(string $byteStringHex, bool $signed, string $expectedNumber) : void
    {
        $number = BigInteger::fromBytes(hex2bin($byteStringHex), $signed);
        self::assertSame($expectedNumber, (string) $number);
    }

    public static function providerFromBytes() : Generator
    {
        foreach (self::providerToBytes() as [$expectedNumber, $signed, $byteStringHex]) {
            yield [$byteStringHex, $signed, $expectedNumber];

            // test with extra leading bits: these should return the same number
            $prefix = ($expectedNumber[0] === '-') ? 'FF' : '00';
            yield [$prefix . $byteStringHex, $signed, $expectedNumber];
        }
    }

    public function testFromBytesWithEmptyString() : void
    {
        $this->expectException(NumberFormatException::class);
        BigInteger::fromBytes('');
    }

    #[DataProvider('providerToBytes')]
    public function testToBytes(string $number, bool $signed, string $expectedByteStringHex) : void
    {
        $byteString = BigInteger::of($number)->toBytes($signed);
        self::assertSame($expectedByteStringHex, strtoupper(bin2hex($byteString)));
    }

    public static function providerToBytes() : array
    {
        return [
            ['-549755813889', true, 'FF7FFFFFFFFF'],
            ['-549755813888', true, '8000000000'],
            ['-549755813887', true, '8000000001'],
            ['-4294967297', true, 'FEFFFFFFFF'],
            ['-4294967296', true, 'FF00000000'],
            ['-4294967295', true, 'FF00000001'],
            ['-4294836226', true, 'FF0001FFFE'],
            ['-4294836225', true, 'FF0001FFFF'],
            ['-4294836224', true, 'FF00020000'],
            ['-4294836223', true, 'FF00020001'],
            ['-2147483650', true, 'FF7FFFFFFE'],
            ['-2147483649', true, 'FF7FFFFFFF'],
            ['-2147483648', true, '80000000'],
            ['-16777217', true, 'FEFFFFFF'],
            ['-16777216', true, 'FF000000'],
            ['-16777215', true, 'FF000001'],
            ['-16777214', true, 'FF000002'],
            ['-16776961', true, 'FF0000FF'],
            ['-16776960', true, 'FF000100'],
            ['-16776959', true, 'FF000101'],
            ['-131073', true, 'FDFFFF'],
            ['-131072', true, 'FE0000'],
            ['-131071', true, 'FE0001'],
            ['-65537', true, 'FEFFFF'],
            ['-65536', true, 'FF0000'],
            ['-65535', true, 'FF0001'],
            ['-32769', true, 'FF7FFF'],
            ['-32768', true, '8000'],
            ['-32767', true, '8001'],
            ['-256', true, 'FF00'],
            ['-255', true, 'FF01'],
            ['-129', true, 'FF7F'],
            ['-128', true, '80'],
            ['-2', true, 'FE'],
            ['-1', true, 'FF'],
            ['0', false, '00'],
            ['0', true, '00'],
            ['1', false, '01'],
            ['1', true, '01'],
            ['127', false, '7F'],
            ['127', true, '7F'],
            ['128', false, '80'],
            ['128', true, '0080'],
            ['255', false, 'FF'],
            ['255', true, '00FF'],
            ['32767', false, '7FFF'],
            ['32767', true, '7FFF'],
            ['32768', false, '8000'],
            ['32768', true, '008000'],
            ['65535', true, '00FFFF'],
            ['65535', false, 'FFFF'],
            ['8388607', false, '7FFFFF'],
            ['8388607', true, '7FFFFF'],
            ['16777215', false, 'FFFFFF'],
            ['16777215', true, '00FFFFFF'],

            ['-783409810830859048059', true, 'D588013FBB7EADEB85'],
            ['3938956672038786165637', false, 'D588013FBB7EADEB85'],
            ['3938956672038786165637', true, '00D588013FBB7EADEB85'],

            ['-91749892389539817298343095779467869273987239847934', true, 'C138E088BEDB9E9052680AA1AD2E30628126C8D802'],
            ['91749892389539817298343095779467869273987239847934', false, '3EC71F774124616FAD97F55E52D1CF9D7ED93727FE'],
            ['91749892389539817298343095779467869273987239847934', true, '3EC71F774124616FAD97F55E52D1CF9D7ED93727FE'],

            ['-172394526767171329761800221395900583757931491153922', true, '8A0AFA0F6D456BD1E23C617F037FBD9D7ED93727FE'],
            ['-201749892389539817298343095779467869273987239847934', true, 'FF75F505F092BA942E1DC39E80FC8042628126C8D802'],
            ['201749892389539817298343095779467869273987239847934', false, '8A0AFA0F6D456BD1E23C617F037FBD9D7ED93727FE'],
            ['201749892389539817298343095779467869273987239847934', true, '008A0AFA0F6D456BD1E23C617F037FBD9D7ED93727FE'],

            ['-1098858588416703775195092613', true, 'FC730BA48F28911AA6FEA97B'],
            ['-47083115471922296524820850', true, 'D90DC332265BB003412E8E'],
            ['-943932252431784472316642258806', true, 'F415FD8A8DB757E5DEE38D108A'],
            ['-341852484393431150693130917974925642410', true, 'FEFED19B3B597B4992D424EE7BD763F556'],
            ['-283182252236038840253195025971128832705', true, 'FF2AF51502896F66B16A61FA4BEC0FED3F'],
            ['-81924191665932376117353149384682167256990062702727', true, 'C7F1F831608910CA847BBA5B9DF78D73739BB48F79'],
            ['-2895870591297216468400157702565925141502115543810', true, 'FE04C0ABEE0B5802122B7FD1F45DED3CF86FFA84FE'],
            ['-3119445165109880695968047110078654901', true, 'FDA7377A9CBAD40D92C5CCE93A6F164B'],
            ['-2416303054131084339036838954993158636473528506132', true, 'FE58C13944FC5542742BC6EA5E664ADFA371C5A8EC'],
            ['-96872708052717571315623430142461871949402837084399', true, 'BDB78D88F9EF374F0B36B23A65DD2615A59BF41711'],
            ['-406488096345576796461719102940335', true, 'EBF5661F78276E1983AF850C1B51'],
            ['-1136451083331321373659312404693395966315', true, 'FCA9077B9A9F25AFE8EE567A89496BF695'],
            ['-9050047939578973554450039239', true, 'E2C1F9CE23F97DC6337CFE39'],
            ['-5020359323944618072504884280451', true, 'C0A258196411C027A1914FC37D'],
            ['-2328315909540727378430095354579827047745865', true, 'E545B1CAFB737E6A5D994EAA63580370B6B7'],
            ['-431858509646237776589820167124236054397', true, 'FEBB1B139272FB6CCCC9D2CB50DC60D083'],
            ['-708427585499922108210831863881916427823432', true, 'F7DE1E51F72FEE6C276F8CCA9FBC229396B8'],
            ['-810836550712511504071418602', true, 'FD614AAE82643E8BD8199D16'],
            ['-6250094231931483341539582450667557086996', true, 'EDA1F3A2E7B5E2962FB0FB18DEF76618EC'],
            ['-793694724265757042363818257818', true, 'F5FB6F1C9F0A05158C27ACD266'],
            ['-823570289597131761777978989763', true, 'F59AE69F9536B419206BC2A33D'],
            ['-48802520147365020361534264877157131084794152', true, 'FDCFC6487CA9AF6AD8FDC3ECD07F242B9D76D8'],
            ['-29698333286094898756573847056234', true, 'FE8927827B08DDF695EE01D49096'],
            ['-9259920943214103669706837547526283204815967139517', true, 'F9AA02C8B97A22134B8BBED296110C43D555414943'],
            ['-1329712773661524524246281239430805224', true, 'FEFFE8193E33131F8545B3E0F3B24918'],
            ['-1901878901152859921638714839336301950184917', true, 'EA2AE1575DFCFCADE087008E55F43283522B'],
            ['-211851840428790382900097129102333609377496150', true, 'F6800F655A76F12A036BB19813EFB9747483AA'],
            ['-4567388417903078950492095', true, 'FC38D160B3521CD8AD2841'],
            ['-369549145795669040781019236477391675536381', true, 'FBC1FE1F6B3FA12F0C15EBB2D1D8E538CC03'],
            ['-1576138934203079166729265319520498113775', true, 'FB5E3EAED9C484CAE2D719313D1637A311'],
            ['-6479164160394946817944469', true, 'FAA3FBCE9C2BC0BBB6146B'],
            ['-4973786449177733351701364077033', true, 'C138D4473C207E31820C000E17'],
            ['-711073252382098058231709543027', true, 'F70665FDD48CBF58C89DEE558D'],
            ['-6006367085080317337477614662241', true, 'B43061986886506ED2DB7C0D9F'],
            ['-7794702879691249399247142997073094893', true, 'FA22CB7A161C24BF1AA59BEFD13F9713'],
            ['-92678975046563530462161825', true, 'B35676B20EDD0B3DB4945F'],
            ['-15677635845925377762958659902912102793958926170', true, 'FD40FD9112795C35521FE70F98D7AB063937D8A6'],
            ['-4670095405709769649548288171872688313', true, 'FC7C9284F8F7E995E1AA712740D6E747'],
            ['-174516040067109253897831812572', true, 'FDCC1BB70F489CA9DAD93C7224'],
            ['-78545859497424473138903230802367665200', true, 'C4E89E22CED860B9E55E8CEE89272BD0'],
            ['-13497065900825560235714337812912830643153659021435', true, 'F6C3D2A20A423123AD1E0EFD8BDF5BB3A8AA4B3785'],
            ['-5596555173616744352812136520441802', true, 'FEEC118AEF4D06D0F643CD39A0A036'],
            ['-99255515845082927316486933856906', true, 'FB1B38219A9F958666994AC9BD76'],
            ['-214375103475158898441935629946159638332', true, 'FF5EB8DB5D0D435EDD4E7636018B6BD8C4'],
            ['-1317075184740725940950186258631', true, 'EF604CF120DE2E2811BD2D0F39'],
            ['-806295473821796646961606303367289386895057', true, 'F6BE82B44B55536F7AD05829B2D0E446F92F'],
            ['-891519425610904878565592597054470339680', true, 'FD614B9AAE3F585049CBBB14E0D0E643A0'],
            ['-348541235698230937744111503200245977926', true, 'FEF9C96654CDCC22ECB72A1199D5C4ECBA'],
            ['-92998623857448524743006503658281', true, 'FB6A313D59DB9A33234E975A9CD7'],
            ['-136107716767751306827369763', true, '8F6A11E3B0E7D7502672DD'],
            ['-26859090460874041069092334', true, 'E9C85DC9DF834329D8C212'],
            ['-141333871744599151791049755944272203632', true, '95AC161FF4636C67912F85933AD4F090'],
            ['-46954121389748005216797591', true, 'D92913FAA69544689EBC69'],
            ['-596904336055990562119362462070166572406242', true, 'F925DB1686886353785D68E8CA74225D6A1E'],
            ['-1344405286102205040892162', true, 'FEE34FA4B63686D9E0E6FE'],
            ['-35964005242894495033684813312891910672337147', true, 'FE6327499A42D08BAC1085C45A9407C6343705'],
            ['-601198613094225698564174314', true, 'FE0EB33DE1204120F92A8E16'],
            ['-7610885901830744730460664472229465802332051013', true, 'FEAAB7508B4AF39EBD10BB35DEE829EAA085E9BB'],
            ['-1306992083698354560765415156364', true, 'EF80E17CD3A4F68C57C19B9574'],
            ['-5928426295029435752295941280931458449876298746999', true, 'FBF1902BB8510536016F32111424AB0A6CD3383389'],
            ['-9896625370984316971469780243746442575622', true, 'E2EA9B48F364ADED599F896DE43FFE00FA'],
            ['-3443717087795173563395198312020096617495', true, 'F5E13C36CCBFD3BF48E8688F60D14773E9'],
            ['-2245494922030817093415463801401387705038026', true, 'E6391560F819594E246C4D49BD740B1A0B36'],
            ['-69920118812045303946652729', true, 'C629D6C3A1172598A453C7'],
            ['-4649308422069845482348722740', true, 'F0FA2EA5696E1650D31845CC'],
            ['-5944629567503254036792469', true, 'FB152CFC442118BA8E3B6B'],
            ['-127287547939906994889167955101580269246', true, 'A03D4F5B5716DB273873287F6293E942'],
            ['-248002284366499376793080698841650655398888883699', true, 'D48F316557240A553B65D99CA1F51451CE45BA0D'],
            ['-23473333872646511990633448370574', true, 'FED7B9914EFDF4213635B8A38E72'],
            ['-90081289425957678036534683340022400330665092189', true, 'F0389D82E9997BB33DA87E67B4EB1A6B5C2B1BA3'],
            ['-6131774766767685247732809530507373880274956199801', true, 'FBCDF1B5BEDFF595F230E7B317BE86109C9B69D887'],
            ['-61408144425269671111963032819693687372', true, 'D1CD38B99CA0A4509A2EDFE54C616DB4'],
            ['-941883884450958161905398530967842844601553049', true, 'D5C3B40968036422C7EEF2E68DF7334E56EF67'],
            ['-51724104392679606989886993', true, 'D536FEAEB06C0DAAB3E9EF'],
            ['-7845325374205082456600697444541989', true, 'FE7D3212874987D41E2B1FD67759DB'],
            ['-79955574961153464592840377369552710119732773113197', true, 'C94ACC04DB0A580C682581F29DE7F917975ED03293'],
            ['-532195364082905073623316433892356', true, 'E5C2C008BD72041F6A6D25018BFC'],
            ['-8005009342287066447355401475', true, 'E6226956042AF014723AAAFD'],
            ['-337940679109885279728604443841584448', true, 'BEEA3CF59F4C86BED2CC426CF122C0'],
            ['-4605847097510366708547861415', true, 'F11E21F0898355E7A28DC459'],
            ['-3670015713617629611716762176813', true, 'D1AD8A2767FDC96D50E15372D3'],
            ['-649830629891966673736057405236704', true, 'DFF5FBFD829F4C38BED79AC51A20'],
            ['-3709825918305227929540638847125221526396079333457', true, 'FD762DA7043EC89E94DADCB451C4A0ECD5701D9FAF'],
            ['-43178624890438204804613583850151437', true, 'F7AF212255EE0F1FBFCA5B5DB861F3'],
            ['-7905675446580131577998272825821630231042938673', true, 'FE9D7F4BEECA706A5190AACF62EC09E62CB2A8CF'],
            ['-61457863674470726296386500810', true, 'FF396B3F26641B6F2D89CA8736'],
            ['-49308204466516796640462839998', true, 'FF60AD358FCD59D1EF5890DF42'],
            ['-4979647065425719032196358813843940678', true, 'FC40F471BB8B445D875ACB04DB709ABA'],
            ['-72543680230619431272714746061676621865910', true, 'FF2AD03365935BE6E56CABBFE041B283DC4A'],
            ['-233612491615341559436220748', true, 'FF3EC2A1741B823AD9F8E6B4'],
            ['-410572465760268592909453253610755651060159', true, 'FB496F9B6298819BED5A2C9CE83EA41F3E41'],
            ['-4440664570334327036040778362262541337800286366939', true, 'FCF629B6BD303417FDD8BEE8BA60DC0032DDD89325'],
            ['-441843788011967692225334039493087710', true, 'AAE76B59CB82AD64D35F8B5885EE22'],
            ['-8368143068213706891940405000138132955224063160906', true, 'FA46377CAD1AF23A85E9E3A934EDB4475109C6CDB6'],
            ['-829172234357769818909006249228426999062454628039', true, 'FF6EC2A18EA2660725C6EED1C62F883976C6479139'],
            ['-5906586101908186721958760602074196491', true, 'FB8E6ED2410E62DB7516A0C8A3D655F5'],
            ['-108107432789789110223831515005384896', true, 'EB2DE45D03682F7719DE19C5790740'],
            ['-5689187603712743059853316', true, 'FB4B4485A30709DBDE6FFC'],
            ['-2099146868847492866997101785005721844566', true, 'F9D4C704CD95F8CDB3722115B394C904AA'],
            ['-79222222171395055207251479752239744334172', true, 'FF172FD301840D0C03A91C2B8A30C86B46A4'],

            ['-5522712836598149970513187952362000928321873336', true, 'FF085A5DA5FE6298EE2CC3271314D088CF435A48'],
            ['-5626380784733548014139646923816473885490489486', true, 'FF03B450EE0FABA8DF623760716CA5D94EA8D772'],
            ['-83014840727912357366146247329067270452639', true, 'FF0C0A933DC99D7267680165F0648DEF4A61'],
            ['-84461440308411103403421598199499894063437', true, 'FF07CA464BE094F0CA033746E2906BAB7EB3'],
            ['-19888885585920803236496419582873', true, 'FF04F78B70237396B8B928480C67'],
            ['-19350969325241189350680714728748', true, 'FF0BC1A5570DA847041C92C12AD4'],
            ['-75629136915615836759583377746', true, 'FF0BA10B50A4EFB0C8C4FBB6AE'],
            ['-5635103981671263600594400878193141287978133527', true, 'FF03502DC2145B71430CB94A065996E495A217E9'],
            ['-5376200015943869384474757131064327029707233723', true, 'FF0EEC402183DB518AC909E67E9F7794F38C2645'],
            ['-1322065057338692577952426752557825090', true, 'FF016128FE63A069FF7B1DB3A05243BE'],
            ['-83288572456745425681424028120956120704954', true, 'FF0B3CA46D934BA9B001BA99A25FC96E5846'],
            ['-304214315569945292283687790', true, 'FF045C1CE86BB9207E3A4C92'],
        ];
    }

    public function testToBytesNotSignedWithNegativeNumber() : void
    {
        $number = BigInteger::of(-1);
        $this->expectException(NegativeNumberException::class);
        $number->toBytes(false);
    }

    #[DataProvider('providerRandomBits')]
    public function testRandomBits(int $numBits, string $randomBytesHex, string $expectedNumber) : void
    {
        $randomBytesGenerator = function(int $numBytes) use ($randomBytesHex) : string {
            $randomBytes = hex2bin($randomBytesHex);
            $randomBytesLength = strlen($randomBytes);

            if ($randomBytesLength !== $numBytes) {
                self::fail(
                    "randomBits() was expected to request $randomBytesLength bytes, " .
                    "but requested $numBytes bytes instead."
                );
            }

            return $randomBytes;
        };

        $actualNumber = BigInteger::randomBits($numBits, $randomBytesGenerator);

        self::assertBigIntegerEquals($expectedNumber, $actualNumber);
    }

    public static function providerRandomBits() : array
    {
        return [
            [1, '00', '0'],
            [1, '01', '1'],
            [1, '02', '0'],
            [1, '03', '1'],
            [2, '00', '0'],
            [2, '01', '1'],
            [2, '02', '2'],
            [2, '03', '3'],
            [2, '04', '0'],
            [2, '05', '1'],
            [2, '06', '2'],
            [2, '07', '3'],
            [200, '23FA2323B9820B983E098309280D98ACF34793874972398329', '225831908602167927061252758557915341525679553904533200012073'],
            [199, '23FA2323B9820B983E098309280D98ACF34793874972398329', '225831908602167927061252758557915341525679553904533200012073'],
            [199, 'A3FA2323B9820B983E098309280D98ACF34793874972398329', '225831908602167927061252758557915341525679553904533200012073'],
            [198, '23FA2323B9820B983E098309280D98ACF34793874972398329', '225831908602167927061252758557915341525679553904533200012073'],
            [198, 'E3FA2323B9820B983E098309280D98ACF34793874972398329', '225831908602167927061252758557915341525679553904533200012073'],
            [197, '23FA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [197, 'C3FA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [196, '23FA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [196, 'D3FA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [195, '23FA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [195, 'DBFA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [194, '23FA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [194, 'DFFA2323B9820B983E098309280D98ACF34793874972398329', '24964653069794142618507497015270016210404179681684095599401'],
            [193, '23FA2323B9820B983E098309280D98ACF34793874972398329', '12410449599020781090835918168854683378199468792756026573609'],
            [193, 'FFFA2323B9820B983E098309280D98ACF34793874972398329', '12410449599020781090835918168854683378199468792756026573609'],
        ];
    }

    public function testRandomBitsWithNegativeBits() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        BigInteger::randomBits(-1);
    }

    public function testRandomBitsWithZeroBits() : void
    {
        $random = BigInteger::randomBits(0);
        self::assertBigIntegerEquals('0', $random);
    }

    #[DataProvider('providerRandomRange')]
    public function testRandomRange(string $min, string $max, array $randomBytesHex, string $expectedNumber) : void
    {
        $randomBytesCounter = 0;

        $failCounter = function() use ($randomBytesHex, & $randomBytesCounter) {
            self::fail(sprintf(
                'randomRange() was expected to request random bytes %d time(s), but requested %d time(s) instead.',
                count($randomBytesHex),
                $randomBytesCounter
            ));
        };

        $randomBytesGenerator = function(int $numBytes) use ($randomBytesHex, $failCounter, & $randomBytesCounter) {
            if (! isset($randomBytesHex[$randomBytesCounter])) {
                $randomBytesCounter++;
                $failCounter();
            }

            $randomBytes = hex2bin($randomBytesHex[$randomBytesCounter]);
            $randomBytesLength = strlen($randomBytes);

            if ($randomBytesLength !== $numBytes) {
                self::fail(
                    "randomRange() was expected to request $randomBytesLength bytes, " .
                    "but requested $numBytes bytes instead."
                );
            }

            $randomBytesCounter++;

            return $randomBytes;
        };

        $actualNumber = BigInteger::randomRange($min, $max, $randomBytesGenerator);

        if ($randomBytesCounter !== count($randomBytesHex)) {
            $failCounter();
        }

        self::assertBigIntegerEquals($expectedNumber, $actualNumber);
    }

    public static function providerRandomRange() : array
    {
        return [
            ['0', '1', ['00'], '0'],
            ['0', '1', ['01'], '1'],
            ['0', '1', ['02'], '0'],
            ['0', '1', ['03'], '1'],

            ['-2', '-1', ['00'], '-2'],
            ['-2', '-1', ['01'], '-1'],
            ['-2', '-1', ['02'], '-2'],
            ['-2', '-1', ['03'], '-1'],

            ['-1', '1', ['00'], '-1'],
            ['-1', '1', ['01'], '0'],
            ['-1', '1', ['02'], '1'],
            ['-1', '1', ['03', '02'], '1'],
            ['-1', '1', ['FF', '03', '00'], '-1'],
            ['-1', '1', ['FF', '03', '01'], '0'],
            ['-1', '1', ['FF', '03', '02'], '1'],

            ['1', '255', ['00'], '1'],
            ['1', '255', ['01'], '2'],
            ['1', '255', ['FD'], '254'],
            ['1', '255', ['FE'], '255'],
            ['1', '255', ['FF', '0A'], '11'],

            ['-1', '255', ['0000'], '-1'],
            ['-1', '255', ['0001'], '0'],
            ['-1', '255', ['0100'], '255'],
            ['-1', '255', ['0300'], '255'],
            ['-1', '255', ['0301', 'F0FF'], '254'],
            ['-1', '255', ['FFFF', 'FBFF', 'FAFF'], '254'],
        ];
    }

    public function testRandomRangeWithMinGreaterThanMax() : void
    {
        $this->expectException(MathException::class);
        BigInteger::randomRange(3, 2);
    }

    public function testRandomRangeWithSingleValue() : void
    {
        $value = '123456789123456789123456789123456789';
        $random = BigInteger::randomRange($value, $value);
        self::assertBigIntegerEquals($value, $random);
    }

    public function testSerialize() : void
    {
        $value = '-1234567890987654321012345678909876543210123456789';

        $number = BigInteger::of($value);

        self::assertBigIntegerEquals($value, \unserialize(\serialize($number)));
    }

    public function testDirectCallToUnserialize() : void
    {
        $this->expectException(\LogicException::class);
        BigInteger::zero()->__unserialize([]);
    }

    public function testJsonSerialize() : void
    {
        $value = '-1234567890987654321012345678909876543210123456789';

        $number = BigInteger::of($value);

        self::assertSame($value, $number->jsonSerialize());
    }
}
