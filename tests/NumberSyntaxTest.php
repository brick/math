<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\NumberSyntax;

/**
 * Unit tests for enum NumberSyntax.
 */
class NumberSyntaxTest extends AbstractTestCase
{
    public function testAllContainsEveryCase(): void
    {
        self::assertSame(NumberSyntax::cases(), NumberSyntax::ALL);
    }

    public function testConstants(): void
    {
        self::assertSame([], NumberSyntax::INTEGER);
        self::assertSame([NumberSyntax::DecimalPoint], NumberSyntax::DECIMAL);
        self::assertSame([NumberSyntax::DecimalPoint, NumberSyntax::Exponent], NumberSyntax::SCIENTIFIC);
        self::assertSame([NumberSyntax::Fraction], NumberSyntax::RATIONAL);
    }
}
