<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for class BigNumber.
 *
 * Most of the tests are performed in concrete classes.
 * Only static methods that can be called on BigNumber itself may justify tests here.
 */
class BigNumberTest extends AbstractTestCase
{
    /**
     * @param class-string<BigNumber>          $callingClass  The BigNumber class to call sum() on.
     * @param list<BigNumber|int|float|string> $values        The values to add.
     * @param string                           $expectedClass The expected class name.
     * @param string                           $expectedSum   The expected sum.
     */
    #[DataProvider('providerSum')]
    public function testSum(string $callingClass, array $values, string $expectedClass, string $expectedSum) : void
    {
        $sum = $callingClass::sum(...$values);

        self::assertInstanceOf($expectedClass, $sum);
        self::assertSame($expectedSum, (string) $sum);
    }

    public static function providerSum() : array
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
            [BigNumber::class, [-1, '99', '-0.7', '3/2'], BigRational::class, '1976/20'],
            [BigNumber::class, [-1, '3/2'], BigRational::class, '1/2'],
            [BigNumber::class, [-0.5], BigDecimal::class, '-0.5'],
            [BigNumber::class, [-0.5, 1], BigDecimal::class, '0.5'],
            [BigNumber::class, [-0.5, 1, '0.7'], BigDecimal::class, '1.2'],
            [BigNumber::class, [-0.5, 1, '0.7', '47/7'], BigRational::class, '554/70'],
            [BigNumber::class, ['-1/9'], BigRational::class, '-1/9'],
            [BigNumber::class, ['-1/9', 123], BigRational::class, '1106/9'],
            [BigNumber::class, ['-1/9', 123, '8349.3771'], BigRational::class, '762503939/90000'],
            [BigNumber::class, ['-1/9', '8349.3771', 123], BigRational::class, '762503939/90000']
        ];
    }

    /**
     * @param class-string<BigNumber>          $callingClass  The BigNumber class to call sum() on.
     * @param list<BigNumber|int|float|string> $values        The values to add.
     */
    #[DataProvider('providerSumThrowsRoundingNecessaryException')]
    public function testSumThrowsRoundingNecessaryException(string $callingClass, array $values) : void
    {
        $this->expectException(RoundingNecessaryException::class);
        $callingClass::sum(...$values);
    }

    public static function providerSumThrowsRoundingNecessaryException() : array
    {
        return [
            [BigInteger::class, [1, '1.5']],
            [BigDecimal::class, ['1.5', '1/3']],
        ];
    }
}
