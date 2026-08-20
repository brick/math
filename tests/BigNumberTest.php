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

use function array_map;
use function count;
use function explode;
use function implode;
use function in_array;
use function max;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function strlen;

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
     * @param string|null $expectedValueInMessage The value as rendered in the message, when it differs from $value.
     */
    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testOfInvalidFormatThrowsException(string $value, ?string $expectedValueInMessage = null): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage ?? $value));

        BigNumber::of($value);
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
    public function testParse(string $value, int $digitCount): void
    {
        $expected = BigNumber::of($value);

        $actual = BigNumber::parse($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $digitCount);

        self::assertSame($expected::class, $actual::class);
        self::assertSame($expected->toString(), $actual->toString());
    }

    /**
     * @param int $maxDigits The tightest failing limit: one less than the exact digit count of $value.
     */
    #[DataProvider('providerParseExceeded')]
    public function testParseExceeded(string $value, int $maxDigits): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessage("The number exceeds the maximum number of $maxDigits digits.");

        BigNumber::parse($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    #[DataProvider('providerParse')]
    public function testParseNullableWithNonNullInput(string $value, int $digitCount): void
    {
        $expected = BigNumber::of($value);

        $actual = BigNumber::parseNullable($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $digitCount);

        self::assertNotNull($actual);
        self::assertSame($expected::class, $actual::class);
        self::assertSame($expected->toString(), $actual->toString());
    }

    #[DataProvider('providerParseExceeded')]
    public function testParseNullableWithNonNullInputExceeded(string $value, int $maxDigits): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessage("The number exceeds the maximum number of $maxDigits digits.");

        BigNumber::parseNullable($value, allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    public function testParseNullableWithNullInput(): void
    {
        self::assertNull(BigNumber::parseNullable(null, NumberSyntax::ALL, 1));
        self::assertNull(BigNumber::parseNullable(null, [], 1));
    }

    public static function providerParse(): Generator
    {
        // The digit count is the exact number of digits in the value's final form.
        // Variations (sign, leading zeros) will be generated for each input; digits are also counted as written,
        // so the effective count of each variation is the greater of the two, computed below.
        $values = [
            ['0', 1],
            ['1', 1],
            ['23', 2],
            ['1000', 4],
            [str_repeat('0', 1000) . '1', 1], // 1 digit in its final form, but 1001+ as written: leading zeros count
            ['.0', 2],
            ['.000', 4],
            ['0e100', 1],
            ['0e5', 1],
            ['0e-2', 3],
            ['.001', 4],
            ['.0001', 5],
            ['.0010', 5],
            // Degenerate dot forms: the integral and fractional parts are each optional.
            ['5.', 1],
            ['5.e3', 4],
            ['5.e-3', 4],
            ['.5e3', 3],
            ['.5e-3', 5],
            ['123.45', 5],
            ['1e3', 4],
            ['1.000e3', 4],
            ['1.000e4', 5],
            ['1.2e-2', 4],
            ['1.2e-1', 3],
            ['1.2e0', 2],
            ['1.2e1', 2],
            ['1.2e2', 3],
            ['1.2e3', 4],
            ['1e-9', 10],
            ['1e100', 101],
            // A small final form must not hide an unbounded written length.
            ['0.00000000001e11', 1], // 1 digit in its final form (1), but 14 as written
            ['1e' . str_repeat('0', 20) . '1', 2], // 2 digits in its final form (10), but 22 as written
            ['1/3', 2],
            ['22/7', 3],
            ['2/4', 2],
            ['7/3', 2],
            ['0/5', 2],
            ['1000000/1000000', 14], // counted before simplification
        ];

        foreach ($values as [$raw, $finalFormDigitCount]) {
            foreach (self::generateVariations($raw) as $value) {
                $writtenDigitCount = strlen((string) preg_replace('/[^0-9]/', '', $value));

                yield [$value, max($finalFormDigitCount, $writtenDigitCount)];
            }
        }
    }

    public static function providerParseExceeded(): Generator
    {
        // Every accepted row of the main matrix must be rejected at one digit less.
        foreach (self::providerParse() as [$value, $digitCount]) {
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
        yield ['5.e1000000000', 1_000_000_000]; // 1_000_000_001 digits, via a trailing dot
        yield ['.5e-1000000000', 1_000_000_001]; // 1_000_000_002 digits, via a leading dot
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

    public static function providerParseSyntaxAllowed(): Generator
    {
        foreach (self::syntaxMatrix() as $key => [$value, $syntax, $expectedValue]) {
            if ($expectedValue !== null) {
                yield $key => [$value, $syntax, $expectedValue];
            }
        }
    }

    public static function providerParseSyntaxNotAllowed(): Generator
    {
        foreach (self::syntaxMatrix() as $key => [$value, $syntax, $expectedValue]) {
            if ($expectedValue === null) {
                yield $key => [$value, $syntax];
            }
        }
    }

    /**
     * The syntax check runs before the exponent and digit-count checks.
     */
    #[DataProvider('providerParseSyntaxIsCheckedFirst')]
    public function testParseSyntaxIsCheckedFirst(string $value, string $expectedMessage): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact($expectedMessage);

        BigNumber::parse($value, [], 1);
    }

    public static function providerParseSyntaxIsCheckedFirst(): array
    {
        return [
            ['1e99999999999999999999', 'The exponent syntax is not allowed.'], // would otherwise be exponentTooLarge
            ['12.5', 'The decimal point syntax is not allowed.'], // would otherwise be tooManyDigits
        ];
    }

    /**
     * The format check runs before the syntax check: a value that is not a number in any syntax is reported as
     * invalid, not as using a disallowed syntax.
     */
    #[DataProvider('providerParseFormatIsCheckedBeforeSyntax')]
    public function testParseFormatIsCheckedBeforeSyntax(string $value): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $value));

        BigNumber::parse($value, [], 10);
    }

    public static function providerParseFormatIsCheckedBeforeSyntax(): array
    {
        return [
            ['a/b'],
            ['1/2/3'],
            ['1ex'],
            ['1.x'],
        ];
    }

    public function testParseNullableWithNonNullInputSyntaxNotAllowed(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact('The decimal point syntax is not allowed.');

        BigNumber::parseNullable('1.5', [], 10);
    }

    /**
     * Format-error messages truncate the rejected value: untrusted input of unbounded length must not be
     * copied wholesale into exception messages and logs.
     */
    #[DataProvider('providerParseTruncatesValueInErrorMessage')]
    public function testParseTruncatesValueInErrorMessage(string $value, string $expectedValueInMessage): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage));

        BigNumber::parse($value, NumberSyntax::ALL, 10);
    }

    public static function providerParseTruncatesValueInErrorMessage(): array
    {
        return [
            'integer form' => [str_repeat('1', 43) . 'X', str_repeat('1', 40) . '...'],
            'rational form' => [str_repeat('1', 50) . 'X/2', str_repeat('1', 40) . '...'],
            'digitless form' => ['.e' . str_repeat('3', 60), '.e' . str_repeat('3', 38) . '...'],
            'at the threshold, kept whole' => [str_repeat('1', 39) . 'X', str_repeat('1', 39) . 'X'],
            'just above the threshold' => [str_repeat('1', 40) . 'X', str_repeat('1', 40) . '...'],
        ];
    }

    /**
     * The truncation is a property of the exception, and applies to of() as well.
     */
    public function testOfTruncatesValueInErrorMessage(): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', str_repeat('1', 40) . '...'));

        BigNumber::of(str_repeat('1', 43) . 'X');
    }

    /**
     * Format-error messages escape the rejected value in the repr style shared by Python, Go and Rust:
     * untrusted input must not put raw control characters or invalid UTF-8 into exception messages and logs,
     * while staying as readable as possible. ASCII controls are escaped as `\t`, `\n`, `\r` or `\xHH`; the
     * backslash and the double quote are escaped as `\\` and `\"`, so the quotes delimiting the value are
     * unambiguous and every backslash in the rendered value starts an escape. When the whole value is valid
     * UTF-8, non-ASCII text is kept as-is, except for a short list of invisible characters commonly found in
     * copy-pasted numbers, escaped as `\u{XXXX}`: kept, they would misleadingly look valid. When the value is
     * not valid UTF-8, every non-ASCII byte is escaped as `\xHH`. Single characters in "not valid in base /
     * alphabet" messages are rendered the same way, always quoted.
     */
    #[DataProvider('providerParseEscapesValueInErrorMessage')]
    public function testParseEscapesValueInErrorMessage(string $value, string $expectedValueInMessage): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $expectedValueInMessage));

        BigNumber::parse($value, NumberSyntax::ALL, 10);
    }

    public static function providerParseEscapesValueInErrorMessage(): array
    {
        return [
            'control character' => ["12\x0034", '12\x0034'],
            'terminal escape sequence' => ["\x1B[31m1", '\x1B[31m1'],
            'DEL' => ["1\x7F", '1\x7F'],
            'tab' => ["1\t2", '1\t2'],
            'carriage return and newline' => ["1\r\n", '1\r\n'],
            'backslash, escaped unambiguously' => ['1\x0A', '1\\\\x0A'],
            'spoofed invisible escape, rendered inert' => ['1\u{00A0}2', '1\\\\u{00A0}2'],
            'double quote, escaped unambiguously' => ['1"2', '1\"2'],
            'angle bracket, left alone' => ['1<2', '1<2'],
            'dollar sign, left alone' => ['$100', '$100'],
            'visible Latin-1 letter, kept' => ["1\u{E9}", "1\u{E9}"],
            'visible Unicode minus sign, kept' => ["1\u{2212}1", "1\u{2212}1"],
            'visible fullwidth digits, kept' => ["\u{FF11}\u{FF12}", "\u{FF11}\u{FF12}"],
            'visible astral emoji, kept' => ["1\u{1F600}", "1\u{1F600}"],
            'invisible no-break space, escaped' => ["1\u{00A0}000", '1\u{00A0}000'],
            'invisible narrow no-break space, escaped' => ["1\u{202F}000", '1\u{202F}000'],
            'invisible zero-width space, escaped' => ["1\u{200B}2", '1\u{200B}2'],
            'invisible byte order mark, escaped' => ["\u{FEFF}123", '\u{FEFF}123'],
            'other invisible characters, kept as-is' => ["1\u{2028}\u{202E}2", "1\u{2028}\u{202E}2"],
            'broken UTF-8: lone lead byte' => ["1\xC3", '1\xC3'],
            'broken UTF-8: lone continuation byte' => ["1\xA9", '1\xA9'],
            'broken UTF-8: overlong encoding' => ["1\xC0\xAF", '1\xC0\xAF'],
            'broken UTF-8: surrogate half' => ["1\xED\xA0\x80", '1\xED\xA0\x80'],
            'broken UTF-8 escapes every non-ASCII byte' => ["1\u{E9}2\xFF", '1\xC3\xA92\xFF'],
            'truncation drops a cut multibyte character' => [str_repeat('1', 39) . "\u{20AC}XXX", str_repeat('1', 39) . '...'],
            'truncation keeps a whole multibyte character' => [str_repeat('1', 37) . "\u{20AC}XX", str_repeat('1', 37) . "\u{20AC}..."],
        ];
    }

    #[DataProvider('providerInvalidMaxDigits')]
    public function testParseWithInvalidMaxDigits(int $maxDigits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The maximum number of digits must be a positive integer.');

        /** @phpstan-ignore argument.type */
        BigNumber::parse('1', allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    #[DataProvider('providerInvalidMaxDigits')]
    public function testParseNullableWithNonNullInputAndInvalidMaxDigits(int $maxDigits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The maximum number of digits must be a positive integer.');

        /** @phpstan-ignore argument.type */
        BigNumber::parseNullable('1', allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
    }

    #[DataProvider('providerInvalidMaxDigits')]
    public function testParseNullableWithNullInputAndInvalidMaxDigits(int $maxDigits): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The maximum number of digits must be a positive integer.');

        /** @phpstan-ignore argument.type */
        BigNumber::parseNullable(null, allowedSyntax: NumberSyntax::ALL, maxDigits: $maxDigits);
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
     * Yields every subset of syntax features against every top-level form, as [value, syntax, expected value].
     * The expected value is null when the value uses a feature that is not allowed.
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

        $syntaxes = [
            [],
            [NumberSyntax::DecimalPoint],
            [NumberSyntax::Exponent],
            [NumberSyntax::Fraction],
            [NumberSyntax::DecimalPoint, NumberSyntax::Exponent],
            [NumberSyntax::DecimalPoint, NumberSyntax::Fraction],
            [NumberSyntax::Exponent, NumberSyntax::Fraction],
            [NumberSyntax::DecimalPoint, NumberSyntax::Exponent, NumberSyntax::Fraction],
        ];

        foreach ($syntaxes as $syntax) {
            foreach ($values as [$value, $features, $expectedValue]) {
                $allowed = true;

                foreach ($features as $feature) {
                    if (! in_array($feature, $syntax, true)) {
                        $allowed = false;

                        break;
                    }
                }

                $key = sprintf(
                    "'%s' with [%s]",
                    $value,
                    implode(', ', array_map(static fn (NumberSyntax $case) => $case->name, $syntax)),
                );

                yield $key => [$value, $syntax, $allowed ? $expectedValue : null];
            }
        }
    }

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
