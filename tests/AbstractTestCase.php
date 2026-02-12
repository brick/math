<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use PHPUnit\Framework\TestCase;

use function count;
use function ctype_digit;
use function explode;
use function ltrim;
use function preg_quote;
use function str_ends_with;
use function strlen;
use function substr;

/**
 * Base class for math tests.
 */
abstract class AbstractTestCase extends TestCase
{
    final protected function expectExceptionMessageExact(string $message): void
    {
        $this->expectExceptionMessageMatches('/^' . preg_quote($message, '/') . '$/');
    }

    /**
     * @param string     $expected The expected value, as a string.
     * @param BigInteger $actual   The BigInteger instance to test.
     */
    final protected static function assertBigIntegerEquals(string $expected, BigInteger $actual): void
    {
        self::assertSame($expected, $actual->toString());
    }

    /**
     * @param string     $expected The expected string representation.
     * @param BigDecimal $actual   The BigDecimal instance to test.
     */
    final protected static function assertBigDecimalEquals(string $expected, BigDecimal $actual): void
    {
        self::assertSame($expected, $actual->toString());

        [$unscaledValue, $scale] = self::getUnscaledValueAndScale($expected);

        self::assertSame($unscaledValue, $actual->getUnscaledValue()->toString());
        self::assertSame($scale, $actual->getScale());
    }

    /**
     * @param string      $expected The expected string representation.
     * @param BigRational $actual   The BigRational instance to test.
     */
    final protected static function assertBigRationalEquals(string $expected, BigRational $actual): void
    {
        self::assertSame($expected, $actual->toString());

        [$numerator, $denominator] = self::getNumeratorAndDenominator($expected);

        self::assertSame($numerator, $actual->getNumerator()->toString());
        self::assertSame($denominator, $actual->getDenominator()->toString());
    }

    final protected static function isException(string $name): bool
    {
        return str_ends_with($name, 'Exception');
    }

    /**
     * Computes the unscaled value and scale from a properly formatted decimal string.
     *
     * This intentionally uses a different algorithm than `BigDecimal::of()`, to increase the chances of catching bugs.
     *
     * @return array{string, int}
     */
    private static function getUnscaledValueAndScale(string $decimal): array
    {
        $message = "Invalid decimal number used in assertion: $decimal";

        $parts = explode('.', $decimal);
        $left = $parts[0];

        if ($left[0] === '-') {
            $left = substr($left, 1);
            $sign = '-';
        } else {
            $sign = '';
        }

        self::assertDigitStringWithNoLeadingZeros($left, $message);

        if (count($parts) === 1) {
            if ($sign === '-') {
                self::assertNotSame('0', $left, $message);
            }

            return [$sign . $left, 0];
        }

        self::assertCount(2, $parts, $message);
        $right = $parts[1];

        self::assertTrue(ctype_digit($right), $message);

        $scale = strlen($right);

        $unscaledValue = ltrim($left . $right, '0');

        if ($unscaledValue === '') {
            $unscaledValue = '0';
        }

        return [$sign . $unscaledValue, $scale];
    }

    /**
     * Extracts the numerator and denominator from a properly formatted rational string.
     *
     * This intentionally uses a different algorithm than `BigRational::of()`, to increase the chances of catching bugs.
     *
     * @return array{string, string}
     */
    private static function getNumeratorAndDenominator(string $rational): array
    {
        $message = "Invalid rational number used in assertion: $rational";

        $parts = explode('/', $rational);
        $numerator = $parts[0];

        if ($numerator[0] === '-') {
            $numerator = substr($numerator, 1);
            $sign = '-';
        } else {
            $sign = '';
        }

        self::assertDigitStringWithNoLeadingZeros($numerator, $message);

        if (count($parts) === 1) {
            if ($sign === '-') {
                self::assertNotSame('0', $numerator, $message);
            }

            return [$sign . $numerator, '1'];
        }

        self::assertCount(2, $parts, $message);
        $denominator = $parts[1];

        self::assertDigitStringWithNoLeadingZeros($denominator, $message);

        return [$sign . $numerator, $denominator];
    }

    private static function assertDigitStringWithNoLeadingZeros(string $digits, string $message): void
    {
        self::assertTrue(ctype_digit($digits), $message);
        self::assertTrue($digits === '0' || ltrim($digits, '0') === $digits, $message);
    }
}
