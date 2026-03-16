<?php

declare(strict_types=1);

namespace Brick\Math\Tests\Internal;

use Brick\Math\Exception\IntegerOverflowException;
use Brick\Math\Internal\Safe;
use Brick\Math\Tests\AbstractTestCase;

use function sprintf;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class SafeTest extends AbstractTestCase
{
    public function testAddThrowsIntegerOverflowException(): void
    {
        $this->expectException(IntegerOverflowException::class);
        $this->expectExceptionMessageExact(sprintf(
            'Cannot compute %d + %d because the result is outside the native integer range [%d, %d].',
            PHP_INT_MAX,
            1,
            PHP_INT_MIN,
            PHP_INT_MAX,
        ));

        Safe::add(PHP_INT_MAX, 1);
    }

    public function testSubThrowsIntegerOverflowException(): void
    {
        $this->expectException(IntegerOverflowException::class);
        $this->expectExceptionMessageExact(sprintf(
            'Cannot compute %d - %d because the result is outside the native integer range [%d, %d].',
            PHP_INT_MIN,
            1,
            PHP_INT_MIN,
            PHP_INT_MAX,
        ));

        Safe::sub(PHP_INT_MIN, 1);
    }

    public function testMulThrowsIntegerOverflowException(): void
    {
        $this->expectException(IntegerOverflowException::class);
        $this->expectExceptionMessageExact(sprintf(
            'Cannot compute %d * %d because the result is outside the native integer range [%d, %d].',
            PHP_INT_MAX,
            2,
            PHP_INT_MIN,
            PHP_INT_MAX,
        ));

        Safe::mul(PHP_INT_MAX, 2);
    }

    public function testNegThrowsIntegerOverflowException(): void
    {
        $this->expectException(IntegerOverflowException::class);
        $this->expectExceptionMessageExact(sprintf(
            'Cannot compute -(%d) because the result is outside the native integer range [%d, %d].',
            PHP_INT_MIN,
            PHP_INT_MIN,
            PHP_INT_MAX,
        ));

        Safe::neg(PHP_INT_MIN);
    }
}
