<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function count;
use function explode;
use function preg_match;
use function sprintf;

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

    #[DataProvider('providerOfInvalidFormatThrowsException')]
    public function testOfInvalidFormatThrowsException(string $value): void
    {
        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessageExact(sprintf('Value "%s" does not represent a valid number.', $value));

        BigNumber::of($value);
    }

    public static function providerOfInvalidFormatThrowsException(): array
    {
        return [
            ['a'],
            [' 1'],
            ['1 '],
            ["\n123"],
            ["123\n"],
            ["1.2\n"],
            ["1e2\n"],
            ["2/3\n"],
            ["1/0\n"],
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
