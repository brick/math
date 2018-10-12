<?php

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Unit tests for class BigInteger.
 */
class BigIntegerTest extends AbstractTestCase
{
    /**
     * @dataProvider providerOf
     *
     * @param string|number $value    The value to convert to a BigInteger.
     * @param string        $expected The expected string value of the result.
     */
    public function testOf($value, $expected)
    {
        $this->assertBigIntegerEquals($expected, BigInteger::of($value));
    }

    /**
     * @return array
     */
    public function providerOf()
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

            [1.2e5, '120000'],
            [-1.2e5, '-120000'],

            ['1e20', '100000000000000000000'],
            ['-3e+50', '-300000000000000000000000000000000000000000000000000'],

            ['0', '0'],
            ['+0', '0'],
            ['-0', '0'],

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

    public function testOfBigIntegerReturnsThis()
    {
        $decimal = BigInteger::of(123);

        $this->assertSame($decimal, BigInteger::of($decimal));
    }

    /**
     * @dataProvider providerOfInvalidFormatThrowsException
     * @expectedException \Brick\Math\Exception\NumberFormatException
     *
     * @param string|number $value
     */
    public function testOfInvalidFormatThrowsException($value)
    {
        BigInteger::of($value);
    }

    /**
     * @return array
     */
    public function providerOfInvalidFormatThrowsException()
    {
        return [
            [''],
            ['a'],
            [' 1'],
            ['1 '],
            ['1.'],
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

    /**
     * @dataProvider providerOfNonConvertibleValueThrowsException
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     *
     * @param float|string $value
     */
    public function testOfNonConvertibleValueThrowsException($value)
    {
        BigInteger::of($value);
    }

    /**
     * @return array
     */
    public function providerOfNonConvertibleValueThrowsException()
    {
        return [
            [1.1],
            ['1e-1'],
            ['7/9'],
        ];
    }

    /**
     * @dataProvider providerParse
     *
     * @param string $number   The number to create.
     * @param int    $base     The base of the number.
     * @param string $expected The expected result in base 10.
     */
    public function testParse($number, $base, $expected)
    {
        $this->assertBigIntegerEquals($expected, BigInteger::parse($number, $base));
    }

    /**
     * @return array
     */
    public function providerParse()
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

    /**
     * @dataProvider providerParseInvalidValueThrowsException
     * @expectedException \InvalidArgumentException
     *
     * @param string $value
     * @param int    $base
     */
    public function testParseInvalidValueThrowsException($value, $base)
    {
        BigInteger::parse($value, $base);
    }

    /**
     * @return array
     */
    public function providerParseInvalidValueThrowsException()
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

    /**
     * @dataProvider providerParseWithInvalidBaseThrowsException
     * @expectedException \InvalidArgumentException
     *
     * @param int $base
     */
    public function testParseWithInvalidBaseThrowsException($base)
    {
        BigInteger::parse('0', $base);
    }

    /**
     * @return array
     */
    public function providerParseWithInvalidBaseThrowsException()
    {
        return [
            [-2],
            [-1],
            [0],
            [1],
            [37]
        ];
    }

    public function testZero()
    {
        $this->assertBigIntegerEquals('0', BigInteger::zero());
        $this->assertSame(BigInteger::zero(), BigInteger::zero());
    }

    public function testOne()
    {
        $this->assertBigIntegerEquals('1', BigInteger::one());
        $this->assertSame(BigInteger::one(), BigInteger::one());
    }

    public function testTen()
    {
        $this->assertBigIntegerEquals('10', BigInteger::ten());
        $this->assertSame(BigInteger::ten(), BigInteger::ten());
    }

    /**
     * @dataProvider providerMin
     *
     * @param array  $values The values to test.
     * @param string $min    The expected minimum value.
     */
    public function testMin(array $values, $min)
    {
        $this->assertBigIntegerEquals($min, BigInteger::min(... $values));
    }

    /**
     * @return array
     */
    public function providerMin()
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMinOfZeroValuesThrowsException()
    {
        BigInteger::min();
    }

    /**
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     */
    public function testMinOfNonIntegerValuesThrowsException()
    {
        BigInteger::min(1, 1.2);
    }

    /**
     * @dataProvider providerMax
     *
     * @param array  $values The values to test.
     * @param string $max    The expected maximum value.
     */
    public function testMax(array $values, $max)
    {
        $this->assertBigIntegerEquals($max, BigInteger::max(... $values));
    }

    /**
     * @return array
     */
    public function providerMax()
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxOfZeroValuesThrowsException()
    {
        BigInteger::max();
    }

    /**
     * @expectedException \Brick\Math\Exception\RoundingNecessaryException
     */
    public function testMaxOfNonIntegerValuesThrowsException()
    {
        BigInteger::max(1, '3/2');
    }

    /**
     * @dataProvider providerPlus
     *
     * @param string $a The base number.
     * @param string $b The number to add.
     * @param string $r The expected result.
     */
    public function testPlus($a, $b, $r)
    {
        $this->assertBigIntegerEquals($r, BigInteger::of($a)->plus($b));
    }

    /**
     * @return array
     */
    public function providerPlus()
    {
        return [
            ['5165450198704521651351654564564089798441', '0', '5165450198704521651351654564564089798441'],
            ['-5165450198704521651351654564564089798441', '0', '-5165450198704521651351654564564089798441'],
            ['5165450198704521651351654564564089798441', '-5165450198704521651351654564564089798441', '0'],
            ['-5165450198704521651351654564564089798441', '5165450198704521651351654564564089798441', '0'],

            ['3493049309220392055810', '9918493493849898938928310121', '9918496986899208159320365931'],
            ['546254089287665464650654', '-4654654565726542654005465', '-4108400476438877189354811'],
            ['-54654654625426504062224', '406546504670332465465435004', '406491850015707038961372780'],
            ['-78706406576549688403246', '-3064672987984605465406546', '-3143379394561155153809792']
        ];
    }

    /**
     * @dataProvider providerMinus
     *
     * @param string $a The base number.
     * @param string $b The number to subtract.
     * @param string $r The expected result.
     */
    public function testMinus($a, $b, $r)
    {
        $this->assertBigIntegerEquals($r, BigInteger::of($a)->minus($b));
    }

    /**
     * @return array
     */
    public function providerMinus()
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
     * @dataProvider providerMultipliedBy
     *
     * @param string $a The base number.
     * @param string $b The number to multiply.
     * @param string $r The expected result.
     */
    public function testMultipliedBy($a, $b, $r)
    {
        $this->assertBigIntegerEquals($r, BigInteger::of($a)->multipliedBy($b));
    }

    /**
     * @return array
     */
    public function providerMultipliedBy()
    {
        return [
            ['123456789098765432101234567890987654321', '1', '123456789098765432101234567890987654321'],
            ['123456789098765432101234567890987654321', '-1', '-123456789098765432101234567890987654321'],
            ['15892588375910581333', '2485910409339228962451', '39507550875019745254366764864945838527183'],
            ['341581435989834012309', '-91050393818389238433', '-31101124267925302088072082300643257871797'],
            ['-1204902920503999920003', '1984389583950290232332', '-2390996805119422027350037939263960284136996'],
            ['-991230349304902390122', '-3483910549230593053437', '3453357870660875087266990729629471366949314'],

            ['1274837942798479387498237897498734984', 30, '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', 30.0, '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', '30', '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', '30.0', '38245138283954381624947136924962049520'],
            ['1274837942798479387498237897498734984', '90/3', '38245138283954381624947136924962049520'],
        ];
    }

    /**
     * @dataProvider providerDividedBy
     *
     * @param string $number   The base number.
     * @param string $divisor  The divisor.
     * @param string $expected The expected result, or a class name if an exception is expected.
     */
    public function testDividedBy($number, $divisor, $expected)
    {
        $number = BigInteger::of($number);

        if ($this->isException($expected)) {
            $this->expectException($expected);
        }

        $actual = $number->dividedBy($divisor);

        if (! $this->isException($expected)) {
            $this->assertBigIntegerEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function providerDividedBy()
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
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDividedByWithInvalidRoundingModeThrowsException()
    {
        BigInteger::of(1)->dividedBy(2, -1);
    }

    /**
     * @dataProvider providerDividedByWithRoundingMode
     *
     * @param integer     $roundingMode The rounding mode.
     * @param string      $number       The number to round.
     * @param string|null $ten          The expected rounding divided by 10, or null if an exception is expected.
     * @param string|null $hundred      The expected rounding divided by 100 or null if an exception is expected.
     * @param string|null $thousand     The expected rounding divided by 1000, or null if an exception is expected.
     */
    public function testDividedByWithRoundingMode($roundingMode, $number, $ten, $hundred, $thousand)
    {
        $number = BigInteger::of($number);

        $this->doTestDividedByWithRoundingMode($roundingMode, $number, '1', $ten, $hundred, $thousand);
        $this->doTestDividedByWithRoundingMode($roundingMode, $number->negated(), '-1', $ten, $hundred, $thousand);
    }

    /**
     * @param integer     $roundingMode The rounding mode.
     * @param BigInteger  $number       The number to round.
     * @param string      $divisor      The divisor.
     * @param string|null $ten          The expected rounding to a scale of two, or null if an exception is expected.
     * @param string|null $hundred          The expected rounding to a scale of one, or null if an exception is expected.
     * @param string|null $thousand         The expected rounding to a scale of zero, or null if an exception is expected.
     */
    private function doTestDividedByWithRoundingMode($roundingMode, BigInteger $number, $divisor, $ten, $hundred, $thousand)
    {
        foreach ([$ten, $hundred, $thousand] as $expected) {
            $divisor .= '0';

            if ($expected === null) {
                $this->expectException(RoundingNecessaryException::class);
            }

            $actual = $number->dividedBy($divisor, $roundingMode);

            if ($expected !== null) {
                $this->assertBigIntegerEquals($expected, $actual);
            }
        }
    }

    /**
     * @return array
     */
    public function providerDividedByWithRoundingMode()
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
     * @dataProvider providerQuotientAndRemainder
     *
     * @param string $dividend The dividend.
     * @param string $divisor  The divisor.
     * @param string $quotient The expected quotient.
     */
    public function testQuotient($dividend, $divisor, $quotient)
    {
        $this->assertBigIntegerEquals($quotient, BigInteger::of($dividend)->quotient($divisor));
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testQuotientOfZeroThrowsException()
    {
        BigInteger::of(1)->quotient(0);
    }

    /**
     * @dataProvider providerQuotientAndRemainder
     *
     * @param string $dividend  The dividend.
     * @param string $divisor   The divisor.
     * @param string $quotient  The expected quotient (ignore for this test).
     * @param string $remainder The expected remainder.
     */
    public function testRemainder($dividend, $divisor, $quotient, $remainder)
    {
        $this->assertBigIntegerEquals($remainder, BigInteger::of($dividend)->remainder($divisor));
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testRemainerOfZeroThrowsException()
    {
        BigInteger::of(1)->remainder(0);
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
        [$q, $r] = BigInteger::of($dividend)->quotientAndRemainder($divisor);

        $this->assertBigIntegerEquals($quotient, $q);
        $this->assertBigIntegerEquals($remainder, $r);
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testQuotientAndRemainerOfZeroThrowsException()
    {
        BigInteger::of(1)->quotientAndRemainder(0);
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

            ['123456789098765432101234567890987654321', '1', '123456789098765432101234567890987654321', '0'],
            ['123456789098765432101234567890987654321', '-1', '-123456789098765432101234567890987654321', '0'],
            ['1282493059039502950823948435791053205342314', '24342491090593053', '52685366270303839158198740', '4167539367989094'],
            ['1000000000000000000000000000000000000000000000', '7777777777777777', '128571428571428584285714285714', '2232222222222222'],
            ['999999999999999999999999999999999999999999999', '22221222222', '45002025091579141312274843781092897', '13737242865'],
            ['49283205308081983923480483094304390249024223', '-23981985358744892239240813', '-2055009398548863185', '20719258837232321643854818'],
            ['-8378278174814983902084304176539029302438924', '384758527893793829309012129991', '-21775419041855', '-367584271343844173835372665619'],
            ['-444444444444444444444444444444444444411111', '-33333333333333', '13333333333333466666666666667', '-33333333300000'],
        ];
    }

    /**
     * @expectedException \Brick\Math\Exception\DivisionByZeroException
     */
    public function testQuotientAndRemainderByZeroThrowsException()
    {
        BigInteger::of(1)->quotientAndRemainder(0);
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
        $this->assertBigIntegerEquals($expected, BigInteger::of($number)->power($exponent));
    }

    /**
     * @return array
     */
    public function providerPower()
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

    /**
     * @dataProvider providerPowerWithInvalidExponentThrowsException
     * @expectedException \InvalidArgumentException
     *
     * @param int $power
     */
    public function testPowerWithInvalidExponentThrowsException($power)
    {
        BigInteger::of(1)->power($power);
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
     * @dataProvider providerGcd
     *
     * @param string $a   The first number.
     * @param string $b   The second number.
     * @param string $gcd The expected GCD.
     */
    public function testGcd($a, $b, $gcd)
    {
        $a = BigInteger::of($a);
        $b = BigInteger::of($b);

        $this->assertBigIntegerEquals($gcd, $a->gcd($b));
    }

    /**
     * @return \Generator
     */
    public function providerGcd()
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

    /**
     * @dataProvider providerAbs
     *
     * @param string $number   The number as a string.
     * @param string $expected The expected absolute result.
     */
    public function testAbs($number, $expected)
    {
        $this->assertBigIntegerEquals($expected, BigInteger::of($number)->abs());
    }

    /**
     * @return array
     */
    public function providerAbs()
    {
        return [
            ['0', '0'],
            ['123456789012345678901234567890', '123456789012345678901234567890'],
            ['-123456789012345678901234567890', '123456789012345678901234567890'],
        ];
    }

    /**
     * @dataProvider providerNegated
     *
     * @param string $number   The number to negate as a string.
     * @param string $expected The expected negated result.
     */
    public function testNegated($number, $expected)
    {
        $this->assertBigIntegerEquals($expected, BigInteger::of($number)->negated());
    }

    /**
     * @return array
     */
    public function providerNegated()
    {
        return [
            ['0', '0'],
            ['123456789012345678901234567890', '-123456789012345678901234567890'],
            ['-123456789012345678901234567890', '123456789012345678901234567890'],
        ];
    }

    /**
     * @dataProvider providerOr
     *
     * @param string $a The base number as a string.
     * @param string $b The second operand as a string.
     * @param string $c The expected result.
     */
    public function testOr($a, $b, $c)
    {
        $this->assertBigIntegerEquals($c, BigInteger::of($a)->or($b));
    }

    /**
     * @return array
     */
    public function providerOr()
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
     * @dataProvider providerAnd
     *
     * @param string $a The base number as a string.
     * @param string $b The second operand as a string.
     * @param string $c The expected result.
     */
    public function testAnd($a, $b, $c)
    {
        $this->assertBigIntegerEquals($c, BigInteger::of($a)->and($b));
    }

    /**
     * @return array
     */
    public function providerAnd()
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
     * @dataProvider providerXor
     *
     * @param string $a The base number as a string.
     * @param string $b The second operand as a string.
     * @param string $c The expected result.
     */
    public function testXor($a, $b, $c)
    {
        $this->assertBigIntegerEquals($c, BigInteger::of($a)->xor($b));
    }

    /**
     * @return array
     */
    public function providerXor()
    {
        return [
            ['-1', '-2', '1'],
            ['1', '-2', '-1'],
            ['-1', '2', '-3'],
            ['0', '1', '1'],
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

    /**
     * @dataProvider providerShiftedLeft
     *
     * @param string $a The base number as a string.
     * @param int    $b The distance to shift.
     * @param string $c The expected shifted result.
     */
    public function testShiftedLeft($a, $b, $c)
    {
        $this->assertBigIntegerEquals($c, BigInteger::of($a)->shiftedLeft($b));
    }

    /**
     * @dataProvider providerShiftedLeft
     *
     * @param string $a The base number as a string.
     * @param int    $b The distance to shift, negated.
     * @param string $c The expected shifted result.
     */
    public function testShiftedRight($a, $b, $c)
    {
        $this->assertBigIntegerEquals($c, BigInteger::of($a)->shiftedRight(- $b));
    }

    /**
     * @return array
     */
    public function providerShiftedLeft()
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
     * @dataProvider providerCompareTo
     *
     * @param string $a The base number as a string.
     * @param string $b The number to compare to as a string.
     * @param int    $c The expected comparison result.
     */
    public function testCompareTo($a, $b, $c)
    {
        $this->assertSame($c, BigInteger::of($a)->compareTo($b));
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
        $this->assertSame($c === 0, BigInteger::of($a)->isEqualTo($b));
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
        $this->assertSame($c < 0, BigInteger::of($a)->isLessThan($b));
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
        $this->assertSame($c <= 0, BigInteger::of($a)->isLessThanOrEqualTo($b));
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
        $this->assertSame($c > 0, BigInteger::of($a)->isGreaterThan($b));
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
        $this->assertSame($c >= 0, BigInteger::of($a)->isGreaterThanOrEqualTo($b));
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
     * @dataProvider providerSign
     *
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    public function testGetSign($number, $sign)
    {
        $this->assertSame($sign, BigInteger::of($number)->getSign());
    }

    /**
     * @dataProvider providerSign
     *
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    public function testIsZero($number, $sign)
    {
        $this->assertSame($sign === 0, BigInteger::of($number)->isZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    public function testIsNegative($number, $sign)
    {
        $this->assertSame($sign < 0, BigInteger::of($number)->isNegative());
    }

    /**
     * @dataProvider providerSign
     *
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    public function testIsNegativeOrZero($number, $sign)
    {
        $this->assertSame($sign <= 0, BigInteger::of($number)->isNegativeOrZero());
    }

    /**
     * @dataProvider providerSign
     *
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    public function testIsPositive($number, $sign)
    {
        $this->assertSame($sign > 0, BigInteger::of($number)->isPositive());
    }

    /**
     * @dataProvider providerSign
     *
     * @param int|string $number The number to test.
     * @param int        $sign   The sign of the number.
     */
    public function testIsPositiveOrZero($number, $sign)
    {
        $this->assertSame($sign >= 0, BigInteger::of($number)->isPositiveOrZero());
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
            [PHP_INT_MIN, -1],

            [ '1000000000000000000000000000000000000000000000000000000000000000000000000000000000', 1],
            ['-1000000000000000000000000000000000000000000000000000000000000000000000000000000000', -1]
        ];
    }

    /**
     * @dataProvider providerToScale
     *
     * @param string $number
     * @param int    $scale
     * @param string $expected
     */
    public function testToScale($number, $scale, $expected)
    {
        $this->assertBigDecimalEquals($expected, BigInteger::of($number)->toScale($scale));
    }

    /**
     * @return array
     */
    public function providerToScale()
    {
        return [
            ['12345678901234567890123456789', 0, '12345678901234567890123456789'],
            ['12345678901234567890123456789', 1, '12345678901234567890123456789.0'],
            ['12345678901234567890123456789', 2, '12345678901234567890123456789.00'],
        ];
    }

    /**
     * @dataProvider providerToInt
     *
     * @param int $number
     */
    public function testToInt($number)
    {
        $this->assertSame($number, BigInteger::of((string) $number)->toInt());
    }

    /**
     * @return array
     */
    public function providerToInt()
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
     * @expectedException \Brick\Math\Exception\IntegerOverflowException
     */
    public function testToIntNegativeOverflowThrowsException()
    {
        BigInteger::of(PHP_INT_MIN)->minus(1)->toInt();
    }

    /**
     * @expectedException \Brick\Math\Exception\IntegerOverflowException
     */
    public function testToIntPositiveOverflowThrowsException()
    {
        BigInteger::of(PHP_INT_MAX)->plus(1)->toInt();
    }

    /**
     * @dataProvider providerToFloat
     *
     * @param string $value The big integer value.
     * @param float  $float The expected float value.
     */
    public function testToFloat($value, $float)
    {
        $this->assertSame($float, BigInteger::of($value)->toFloat());
    }

    /**
     * @return array
     */
    public function providerToFloat()
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
     * @dataProvider providerToBase
     *
     * @param string $number   The number to convert.
     * @param int    $base     The base to convert the number to.
     * @param string $expected The expected result.
     */
    public function testToBase($number, $base, $expected)
    {
        $this->assertSame($expected, BigInteger::parse($number)->toBase($base));
        $this->assertSame('-' . $expected, BigInteger::parse('-' . $number)->toBase($base));
    }

    /**
     * @return array
     */
    public function providerToBase()
    {
        return [
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
        ];
    }

    /**
     * @dataProvider providerToInvalidBaseThrowsException
     * @expectedException \InvalidArgumentException
     *
     * @param int $base
     */
    public function testToInvalidBaseThrowsException($base)
    {
        BigInteger::of(0)->toBase($base);
    }

    /**
     * @return array
     */
    public function providerToInvalidBaseThrowsException()
    {
        return [
            [-2],
            [-1],
            [0],
            [1],
            [37]
        ];
    }

    public function testSerialize()
    {
        $value = '-1234567890987654321012345678909876543210123456789';

        $number = BigInteger::of($value);

        $this->assertBigIntegerEquals($value, unserialize(serialize($number)));
    }

    /**
     * @expectedException \LogicException
     */
    public function testDirectCallToUnserialize()
    {
        BigInteger::zero()->unserialize('123');
    }

    public function testJsonSerialize()
    {
        $value = '-1234567890987654321012345678909876543210123456789';

        $number = BigInteger::of($value);

        $this->assertSame($value, $number->jsonSerialize());
    }
}
