<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\InvalidArgumentException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\NumberSyntax;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function count;
use function explode;
use function in_array;
use function max;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function strlen;

use const PHP_INT_MAX;

/**
 * Unit tests for class BigNumber.
 *
 * Most of the tests are performed in concrete classes.
 * Only static methods that can be called on BigNumber itself may justify tests here.
 */
class BigNumberTest extends AbstractTestCase
{
    #[DataProvider('providerOf')]
    public function testOf(BigNumber|int|string $value, string $expectedClass, string $expectedValue): void
    {
        $result = BigNumber::of($value);

        self::assertSame($expectedClass, $result::class);
        self::assertSame($expectedValue, $result->toString());
    }

    #[DataProvider('providerOf')]
    public function testOfNullableWithNonNullInput(mixed $value, string $expectedClass, string $expectedValue): void
    {
        $result = BigNumber::ofNullable($value);

        self::assertNotNull($result);
        self::assertSame($expectedClass, $result::class);
        self::assertSame($expectedValue, $result->toString());
    }

    public function testOfNullableWithNullInput(): void
    {
        self::assertNull(BigNumber::ofNullable(null));
    }

    public static function providerOf(): Generator
    {
        // Int values.
        yield [123, BigInteger::class, '123'];
        yield [-123, BigInteger::class, '-123'];

        // BigNumber values.
        yield [BigInteger::of(123), BigInteger::class, '123'];
        yield [BigDecimal::of('123.456'), BigDecimal::class, '123.456'];
        yield [BigRational::of('123/456'), BigRational::class, '41/152'];

        // String values.
        // Variations (sign, leading zeros) will be generated for each input.
        $values = [
            ['0', BigInteger::class, '0'],
            ['1', BigInteger::class, '1'],
            ['123', BigInteger::class, '123'],
            ['123.0', BigDecimal::class, '123.0'],
            ['.0', BigDecimal::class, '0.0'],
            ['.1', BigDecimal::class, '0.1'],
            ['1.', BigDecimal::class, '1'],
            ['1e2', BigDecimal::class, '100'],
            ['1.2e-2', BigDecimal::class, '0.012'],
            ['1.2e-1', BigDecimal::class, '0.12'],
            ['1.2e0', BigDecimal::class, '1.2'],
            ['1.2e1', BigDecimal::class, '12'],
            ['1.2e2', BigDecimal::class, '120'],
            ['1e-2', BigDecimal::class, '0.01'],
            ['1e-3', BigDecimal::class, '0.001'],
            ['2/3', BigRational::class, '2/3'],
            ['1/8', BigRational::class, '1/8'],
            ['2/4', BigRational::class, '1/2'],
            ['0/5', BigRational::class, '0'],
        ];

        foreach ($values as [$number, $expectedClass, $expectedValue]) {
            $isZero = preg_match('/[1-9]/', $expectedValue) !== 1;

            foreach (self::generateVariations($number) as $variation) {
                $negated = ! $isZero && $variation[0] === '-';

                yield [$variation, $expectedClass, $negated ? '-' . $expectedValue : $expectedValue];
            }
        }
    }

    public function testOfEmptyStringThrowsException(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The number must not be empty.');

        BigNumber::of('');
    }

    /**
     * @param string      $value                  The invalid value.
     * @param string|null $expectedValueInMessage The value as rendered in the message, if it differs from $value.
     */
    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testOfInvalidFormatThrowsException(string $value, ?string $expectedValueInMessage = null): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage ?? $value));

        BigNumber::of($value);
    }

    /**
     * @param string      $value                  The invalid value.
     * @param string|null $expectedValueInMessage The value as rendered in the message, if it differs from $value.
     */
    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testOfNullableWithInvalidFormatThrowsException(string $value, ?string $expectedValueInMessage = null): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage ?? $value));

        BigNumber::ofNullable($value);
    }

    public static function providerOfInvalidFormatThrowsException(): array
    {
        return [
            ['a'],
            [' 1'],
            ['1 '],
            ["\n123", '\n123'],
            ["123\n", '123\n'],
            ["1.2\n", '1.2\n'],
            ["1e2\n", '1e2\n'],
            ["2/3\n", '2/3\n'],
            ["1/0\n", '1/0\n'],
            ['+'],
            ['-'],
            ['+a'],
            ['-a'],
            ['a0'],
            ['0a'],
            ['1.a'],
            ['a.1'],
            ['..1'],
            ['1..'],
            ['.1.'],
            ['.'],
            ['1e'],
            ['.e'],
            ['.e1'],
            ['1e+'],
            ['1e-'],
            ['+e1'],
            ['-e2'],
            ['.e3'],
            ['123/-456'],
            ['1e4/2'],
            ['1.2/3'],
            ['1e2/3'],
            [' 1/2'],
            ['1/2 '],
            ['/'],
            // Special chars.
            ["12\u{0663}4", '12\xD9\xA34'],
            ["1\u{00A0}000", '1\xC2\xA0000'],
            ["\0\x7f\x80", '\x00\x7F\x80'],
            ["1 \r\n\t", '1 \r\n\t'],
            // Exception message truncates value at 40 chars.
            [str_repeat('a', 41), str_repeat('a', 40) . '...'],
            // The cut falls between the 2 bytes of the U+00A0 sequence.
            [str_repeat('1', 39) . "\u{00A0}5", str_repeat('1', 39) . '\xC2...'],
        ];
    }

    /**
     * Input designed to force heavy backtracking in the parse regexps must be rejected as an invalid number.
     * If backtracking is not eliminated, these inputs exhaust pcre.backtrack_limit, and the failed PCRE match
     * surfaces as a PlatformException instead of the promised NumberFormatException.
     */
    #[DataProvider('providerOfAdversarialInputThrowsException')]
    public function testOfAdversarialInputThrowsException(string $value): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageMatches('/^Value "[^"]++" does not represent a valid number\.$/');

        BigNumber::of($value);
    }

    public static function providerOfAdversarialInputThrowsException(): array
    {
        return [
            [str_repeat('1', 10_000) . '!'],
            ['.' . str_repeat('1', 2_000_000) . '!'],
            ['1/' . str_repeat('2', 2_000_000) . '!'],
        ];
    }

    /**
     * @param int $digitCount The exact number of digits in $value; parsing must succeed with this limit.
     */
    #[DataProvider('providerParse')]
    public function testParse(string $value, string $expectedClass, string $expectedValue, int $digitCount): void
    {
        $result = BigNumber::parse($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $digitCount);

        self::assertSame($expectedClass, $result::class);
        self::assertSame($expectedValue, $result->toString());
    }

    /**
     * @param int $maxDigits The tightest failing limit: one less than the exact digit count of $value.
     */
    #[DataProvider('providerParseExceeded')]
    public function testParseExceeded(string $value, int $maxDigits): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact("The number exceeds the maximum number of $maxDigits digits.");

        BigNumber::parse($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    #[DataProvider('providerParse')]
    public function testParseNullableWithNonNullInput(string $value, string $expectedClass, string $expectedValue, int $digitCount): void
    {
        $result = BigNumber::parseNullable($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $digitCount);

        self::assertNotNull($result);
        self::assertSame($expectedClass, $result::class);
        self::assertSame($expectedValue, $result->toString());
    }

    #[DataProvider('providerParseExceeded')]
    public function testParseNullableWithNonNullInputExceeded(string $value, int $maxDigits): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact("The number exceeds the maximum number of $maxDigits digits.");

        BigNumber::parseNullable($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    public function testParseNullableWithNullInput(): void
    {
        self::assertNull(BigNumber::parseNullable(null, NumberSyntax::ALL, 1));
        self::assertNull(BigNumber::parseNullable(null, [], 1));
    }

    public function testParseEmptyStringThrowsException(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The number must not be empty.');

        BigNumber::parse('', allowedSyntax: [], maxDigits: 1);
    }

    public function testParseNullableWithEmptyStringThrowsException(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The number must not be empty.');

        BigNumber::parseNullable('', allowedSyntax: [], maxDigits: 1);
    }

    /**
     * @param string      $value                  The invalid value.
     * @param string|null $expectedValueInMessage The value as rendered in the message, if it differs from $value.
     */
    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testParseInvalidFormatThrowsException(string $value, ?string $expectedValueInMessage = null): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage ?? $value));

        BigNumber::parse($value, allowedSyntax: [], maxDigits: 1);
    }

    /**
     * @param string      $value                  The invalid value.
     * @param string|null $expectedValueInMessage The value as rendered in the message, if it differs from $value.
     */
    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testParseNullableWithInvalidFormatThrowsException(string $value, ?string $expectedValueInMessage = null): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage ?? $value));

        BigNumber::parseNullable($value, allowedSyntax: [], maxDigits: 1);
    }

    /**
     * @return Generator<array{string, class-string<BigNumber>, string, int}>
     */
    public static function providerParse(): Generator
    {
        // Variations (sign, leading zeros) will be generated for each input.
        $values = [
            ['0', BigInteger::class, '0'],
            ['1', BigInteger::class, '1'],
            ['23', BigInteger::class, '23'],
            ['1000', BigInteger::class, '1000'],
            ['1' . str_repeat('0', 100), BigInteger::class, '1' . str_repeat('0', 100)],
            ['.0', BigDecimal::class, '0.0'],
            ['.000', BigDecimal::class, '0.000'],
            ['0e5', BigDecimal::class, '0'],
            ['0e100', BigDecimal::class, '0'],
            ['0e-2', BigDecimal::class, '0.00'],
            ['.001', BigDecimal::class, '0.001'],
            ['.0001', BigDecimal::class, '0.0001'],
            ['.0010', BigDecimal::class, '0.0010'],
            ['5.', BigDecimal::class, '5'],
            ['5.e3', BigDecimal::class, '5000'],
            ['5.e-3', BigDecimal::class, '0.005'],
            ['.5e3', BigDecimal::class, '500'],
            ['.5e-3', BigDecimal::class, '0.0005'],
            ['123.45', BigDecimal::class, '123.45'],
            ['1e3', BigDecimal::class, '1000'],
            ['1.0000e2', BigDecimal::class, '100.00'],
            ['1.0000e3', BigDecimal::class, '1000.0'],
            ['1.000e3', BigDecimal::class, '1000'],
            ['1.000e4', BigDecimal::class, '10000'],
            ['1.2e-2', BigDecimal::class, '0.012'],
            ['1.2e-1', BigDecimal::class, '0.12'],
            ['1.2e0', BigDecimal::class, '1.2'],
            ['1.2e1', BigDecimal::class, '12'],
            ['1.2e2', BigDecimal::class, '120'],
            ['1.2e3', BigDecimal::class, '1200'],
            ['1e-9', BigDecimal::class, '0.000000001'],
            ['1e100', BigDecimal::class, '1' . str_repeat('0', 100)],
            ['0.00000000001e11', BigDecimal::class, '1'],
            ['1e' . str_repeat('0', 20) . '1', BigDecimal::class, '10'],
            ['1/3', BigRational::class, '1/3'],
            ['22/7', BigRational::class, '22/7'],
            ['2/4', BigRational::class, '1/2'],
            ['7/3', BigRational::class, '7/3'],
            ['0/5', BigRational::class, '0'],
            [sprintf('9%s/3%s', str_repeat('0', 100), str_repeat('0', 100)), BigRational::class, '3'],
        ];

        foreach ($values as [$number, $expectedClass, $expectedValue]) {
            $isZero = preg_match('/[1-9]/', $expectedValue) !== 1;
            $resultDigitCount = self::countDigits($expectedValue);

            foreach (self::generateVariations($number) as $variation) {
                $negated = ! $isZero && $variation[0] === '-';
                $writtenDigitCount = self::countDigits($variation);

                yield [
                    $variation,
                    $expectedClass,
                    $negated ? '-' . $expectedValue : $expectedValue,
                    max($resultDigitCount, $writtenDigitCount),
                ];
            }
        }
    }

    /**
     * @return Generator<array{string, int}>
     */
    public static function providerParseExceeded(): Generator
    {
        // Every accepted row of the main matrix must be rejected at one digit less.
        foreach (self::providerParse() as [$value, $_expectedClass, $_expectedValue, $digitCount]) {
            if ($digitCount > 1) {
                yield [$value, $digitCount - 1];
            }
        }

        // Rejection-only cases: these numbers cannot appear in providerParse, as they would allocate ~1 GB.
        yield ['1e1000000000', 1_000_000_000]; // 1_000_000_001 digits
        yield ['1e+1000000000', 1_000_000_000]; // 1_000_000_001 digits
        yield ['-1e1000000000', 1_000_000_000]; // 1_000_000_001 digits
        yield ['1e-1000000000', 1_000_000_000]; // 1_000_000_001 digits
        yield ['-0.5e1000000000', 999_999_999]; // 1_000_000_000 digits
        yield ['123.456e-1000000000', 1_000_000_003]; // 1_000_000_004 digits
        yield ['5.e1000000000', 1_000_000_000]; // 1_000_000_001 digits
        yield ['.5e-1000000000', 1_000_000_001]; // 1_000_000_002 digits
    }

    /**
     * An exponent too large to process must be reported as such.
     */
    #[DataProvider('providerParseExponentTooLargeThrowsException')]
    public function testParseExponentTooLargeThrowsException(string $value): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The exponent is too large to be represented as an integer.');

        BigNumber::parse($value, allowedSyntax: NumberSyntax::ALL, maxDigits: 100);
    }

    /**
     * @return list<array{string}>
     */
    public static function providerParseExponentTooLargeThrowsException(): array
    {
        return [
            ['1e1000000000000000000000000000000'],
            ['1e-1000000000000000000000000000000'],
            ['1.5e-' . PHP_INT_MAX], // the exponent fits in a native integer, but the scale overflows
        ];
    }

    /**
     * A number whose digit count overflows a native integer cannot fit within any digit limit, not even PHP_INT_MAX.
     */
    #[DataProvider('providerParseDigitCountOverflow')]
    public function testParseDigitCountOverflow(string $value): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The number exceeds the maximum number of ' . PHP_INT_MAX . ' digits.');

        BigNumber::parse($value, allowedSyntax: NumberSyntax::ALL, maxDigits: PHP_INT_MAX);
    }

    /**
     * @return list<array{string}>
     */
    public static function providerParseDigitCountOverflow(): array
    {
        return [
            ['1e' . PHP_INT_MAX],
            ['1e-' . PHP_INT_MAX],
        ];
    }

    public function testParseWithZeroDenominator(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The denominator of a rational number must not be zero.');

        BigNumber::parse('2/0', NumberSyntax::ALL, 10);
    }

    /**
     * @param list<NumberSyntax> $syntax
     */
    #[DataProvider('providerParseSyntaxAllowed')]
    public function testParseSyntaxAllowed(string $value, array $syntax, string $expectedValue): void
    {
        $number = BigNumber::parse($value, $syntax, 10);

        self::assertSame($expectedValue, $number->toString());
    }

    /**
     * @param list<NumberSyntax> $syntax
     */
    #[DataProvider('providerParseSyntaxNotAllowed')]
    public function testParseSyntaxNotAllowed(string $value, array $syntax): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageMatches('/^The (decimal point|exponent|fraction) syntax is not allowed\.$/');

        BigNumber::parse($value, $syntax, 10);
    }

    /**
     * @param list<NumberSyntax> $syntax
     */
    #[DataProvider('providerParseSyntaxAllowed')]
    public function testParseNullableSyntaxAllowed(string $value, array $syntax, string $expectedValue): void
    {
        $number = BigNumber::parseNullable($value, $syntax, 10);

        self::assertNotNull($number);
        self::assertSame($expectedValue, $number->toString());
    }

    /**
     * @param list<NumberSyntax> $syntax
     */
    #[DataProvider('providerParseSyntaxNotAllowed')]
    public function testParseNullableSyntaxNotAllowed(string $value, array $syntax): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageMatches('/^The (decimal point|exponent|fraction) syntax is not allowed\.$/');

        BigNumber::parseNullable($value, $syntax, 10);
    }

    /**
     * @return Generator<array{string, list<NumberSyntax>, string}>
     */
    public static function providerParseSyntaxAllowed(): Generator
    {
        foreach (self::syntaxMatrix() as [$value, $syntax, $expectedValue]) {
            if ($expectedValue !== null) {
                yield [$value, $syntax, $expectedValue];
            }
        }
    }

    /**
     * @return Generator<array{string, list<NumberSyntax>}>
     */
    public static function providerParseSyntaxNotAllowed(): Generator
    {
        foreach (self::syntaxMatrix() as [$value, $syntax, $expectedValue]) {
            if ($expectedValue === null) {
                yield [$value, $syntax];
            }
        }
    }

    #[DataProvider('providerInvalidMaxDigits')]
    public function testParseWithInvalidMaxDigits(int $maxDigits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExact('The maximum number of digits must be a positive integer.');

        /** @phpstan-ignore argument.type */
        BigNumber::parse('1', allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    #[DataProvider('providerInvalidMaxDigits')]
    public function testParseNullableWithInvalidMaxDigits(int $maxDigits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageExact('The maximum number of digits must be a positive integer.');

        /** @phpstan-ignore argument.type */
        BigNumber::parseNullable('1', allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    public static function providerInvalidMaxDigits(): array
    {
        return [
            [0],
            [-1],
        ];
    }

    /**
     * @param list<BigNumber|int|string> $values
     */
    #[DataProvider('providerMin')]
    public function testMin(array $values, string $expectedClass, string $expectedValue): void
    {
        $result = BigNumber::min(...$values);

        self::assertInstanceOf($expectedClass, $result);
        self::assertSame($expectedValue, $result->toString());
    }

    public static function providerMin(): array
    {
        return [
            [['1', '1.0', '1/1'], BigInteger::class, '1'],
            [['1.0', '1', '1/1'], BigDecimal::class, '1.0'],
            [['1/1', '1.0', '1'], BigRational::class, '1'],
            [[-3, '-4.0', '-4/1'], BigDecimal::class, '-4.0'],
            [[-3, '-4/1', '-4.0'], BigRational::class, '-4'],
            [['2/3', '0.67', '0.6666666666666666666666666667'], BigRational::class, '2/3'],
        ];
    }

    /**
     * @param list<BigNumber|int|string> $values
     */
    #[DataProvider('providerMax')]
    public function testMax(array $values, string $expectedClass, string $expectedValue): void
    {
        $result = BigNumber::max(...$values);

        self::assertInstanceOf($expectedClass, $result);
        self::assertSame($expectedValue, $result->toString());
    }

    public static function providerMax(): array
    {
        return [
            [['1', '1.0', '1/1'], BigInteger::class, '1'],
            [['1.0', '1', '1/1'], BigDecimal::class, '1.0'],
            [['1/1', '1.0', '1'], BigRational::class, '1'],
            [[-3, '-3.0', '-3/1'], BigInteger::class, '-3'],
            [['1/2', '0.5', '0.50'], BigRational::class, '1/2'],
        ];
    }

    /**
     * @param class-string<BigNumber>    $callingClass  The BigNumber class to call sum() on.
     * @param list<BigNumber|int|string> $values        The values to add.
     * @param string                     $expectedClass The expected class name.
     * @param string                     $expectedSum   The expected sum.
     */
    #[DataProvider('providerSum')]
    public function testSum(string $callingClass, array $values, string $expectedClass, string $expectedSum): void
    {
        $sum = $callingClass::sum(...$values);

        self::assertInstanceOf($expectedClass, $sum);
        self::assertSame($expectedSum, $sum->toString());
    }

    public static function providerSum(): array
    {
        return [
            [BigNumber::class, [-1], BigInteger::class, '-1'],
            [BigNumber::class, [-1, '99'], BigInteger::class, '98'],
            [BigInteger::class, [-1, '99'], BigInteger::class, '98'],
            [BigDecimal::class, [-1, '99'], BigDecimal::class, '98'],
            [BigRational::class, [-1, '99'], BigRational::class, '98'],
            [BigNumber::class, [-1, '99', '-0.7'], BigDecimal::class, '97.3'],
            [BigDecimal::class, [-1, '99', '-0.7'], BigDecimal::class, '97.3'],
            [BigRational::class, [-1, '99', '-0.7'], BigRational::class, '973/10'],
            [BigNumber::class, [-1, '99', '-0.7', '3/2'], BigRational::class, '494/5'],
            [BigNumber::class, [-1, '3/2'], BigRational::class, '1/2'],
            [BigNumber::class, ['-0.5'], BigDecimal::class, '-0.5'],
            [BigNumber::class, ['-0.5', 1], BigDecimal::class, '0.5'],
            [BigNumber::class, ['-0.5', 1, '0.7'], BigDecimal::class, '1.2'],
            [BigNumber::class, ['-0.5', 1, '0.7', '47/7'], BigRational::class, '277/35'],
            [BigNumber::class, ['-1/9'], BigRational::class, '-1/9'],
            [BigNumber::class, ['-1/9', 123], BigRational::class, '1106/9'],
            [BigNumber::class, ['-1/9', 123, '8349.3771'], BigRational::class, '762503939/90000'],
            [BigNumber::class, ['-1/9', '8349.3771', 123], BigRational::class, '762503939/90000'],
        ];
    }

    /**
     * @param class-string<BigNumber>    $callingClass The BigNumber class to call sum() on.
     * @param list<BigNumber|int|string> $values       The values to add.
     */
    #[DataProvider('providerSumThrowsRoundingNecessaryException')]
    public function testSumThrowsRoundingNecessaryException(string $callingClass, array $values, string $expectedExceptionMessage): void
    {
        $this->expectException(RoundingNecessaryException::class);
        $this->expectExceptionMessageExact($expectedExceptionMessage);

        $callingClass::sum(...$values);
    }

    public static function providerSumThrowsRoundingNecessaryException(): array
    {
        return [
            [BigInteger::class, [1, '1.5'], 'This decimal number cannot be represented as an integer without rounding.'],
            [BigInteger::class, [1, '1/2'], 'This rational number cannot be represented as an integer without rounding.'],
            [BigDecimal::class, ['1.5', '1/3'], 'This rational number has a non-terminating decimal expansion and cannot be represented as a decimal without rounding.'],
        ];
    }

    /**
     * Yields every combination of number syntax and syntax sets, as [value, syntax, expected value].
     * The expected value is null when the value uses a feature that is not allowed.
     *
     * @return Generator<array{string, list<NumberSyntax>, string|null}>
     */
    private static function syntaxMatrix(): Generator
    {
        // Each value is listed with the exact syntax features it uses.
        $values = [
            ['5', [], '5'],
            ['1.5', [NumberSyntax::DecimalPoint], '1.5'],
            ['5e3', [NumberSyntax::Exponent], '5000'],
            ['1.5e1', [NumberSyntax::DecimalPoint, NumberSyntax::Exponent], '15'],
            ['1/2', [NumberSyntax::Fraction], '1/2'],
        ];

        // All possible syntax sets.
        $syntaxSets = [
            [],
            [NumberSyntax::DecimalPoint],
            [NumberSyntax::Exponent],
            [NumberSyntax::Fraction],
            [NumberSyntax::DecimalPoint, NumberSyntax::Exponent],
            [NumberSyntax::DecimalPoint, NumberSyntax::Fraction],
            [NumberSyntax::Exponent, NumberSyntax::Fraction],
            [NumberSyntax::DecimalPoint, NumberSyntax::Exponent, NumberSyntax::Fraction],
        ];

        foreach ($syntaxSets as $syntaxSet) {
            foreach ($values as [$value, $syntaxes, $expectedValue]) {
                $allowed = true;

                foreach ($syntaxes as $syntax) {
                    if (! in_array($syntax, $syntaxSet, true)) {
                        $allowed = false;

                        break;
                    }
                }

                yield [$value, $syntaxSet, $allowed ? $expectedValue : null];
            }
        }
    }

    private static function countDigits(string $number): int
    {
        return strlen((string) preg_replace('/[^0-9]/', '', $number));
    }

    /**
     * @return Generator<string>
     */
    private static function generateVariations(string $number): Generator
    {
        $parts = explode('/', $number, 2);

        foreach (['', '+', '-'] as $sign) {
            foreach (['', '0', '00'] as $zeros) {
                if (count($parts) === 2) {
                    [$numerator, $denominator] = $parts;

                    foreach (['', '0', '00'] as $denominatorZeros) {
                        yield $sign . $zeros . $numerator . '/' . $denominatorZeros . $denominator;
                    }
                } else {
                    yield $sign . $zeros . $number;
                }
            }
        }
    }
}
