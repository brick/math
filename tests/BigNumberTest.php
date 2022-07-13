<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Iterator;

/**
 * Unit tests for class BigNumber.
 *
 * Most of the tests are performed in concrete classes. Only static methods that can be called on BigNumber itself may
 * justify tests here.
 */
class BigNumberTest extends AbstractTestCase
{
    /**
     * @dataProvider providerSum
     *
     * @param array  $values        The values to add.
     * @param string $expectedClass The expected class name.
     * @param string $expectedSum   The expected sum.
     */
    public function testSum(array $values, string $expectedClass, string $expectedSum): void
    {
        $sum = BigNumber::sum(...$values);

        self::assertInstanceOf($expectedClass, $sum);
        self::assertSame($expectedSum, (string) $sum);
    }

    public function providerSum(): Iterator
    {
        yield [[-1], BigInteger::class, '-1'];
        yield [[-1, '99'], BigInteger::class, '98'];
        yield [[-1, '99', '-0.7'], BigDecimal::class, '97.3'];
        yield [[-1, '99', '-0.7', '3/2'], BigRational::class, '1976/20'];
        yield [[-1, '3/2'], BigRational::class, '1/2'];
        yield [[-0.5], BigDecimal::class, '-0.5'];
        yield [[-0.5, 1], BigDecimal::class, '0.5'];
        yield [[-0.5, 1, '0.7'], BigDecimal::class, '1.2'];
        yield [[-0.5, 1, '0.7', '47/7'], BigRational::class, '554/70'];
        yield [['-1/9'], BigRational::class, '-1/9'];
        yield [['-1/9', 123], BigRational::class, '1106/9'];
        yield [['-1/9', 123, '8349.3771'], BigRational::class, '762503939/90000'];
        yield [['-1/9', '8349.3771', 123], BigRational::class, '762503939/90000'];
    }
}
