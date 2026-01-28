<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\InvalidArgumentException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use LogicException;
use Override;

use function substr;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * An arbitrarily large rational number.
 *
 * This class is immutable.
 */
final readonly class BigRational extends BigNumber
{
    /**
     * The numerator.
     */
    private BigInteger $numerator;

    /**
     * The denominator. Always strictly positive.
     */
    private BigInteger $denominator;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param BigInteger $numerator        The numerator.
     * @param BigInteger $denominator      The denominator.
     * @param bool       $checkDenominator Whether to check the denominator for negative and zero.
     *
     * @throws DivisionByZeroException If the denominator is zero.
     *
     * @pure
     */
    protected function __construct(BigInteger $numerator, BigInteger $denominator, bool $checkDenominator, bool $simplify)
    {
        if ($checkDenominator) {
            if ($denominator->isZero()) {
                throw DivisionByZeroException::denominatorMustNotBeZero();
            }

            if ($denominator->isNegative()) {
                $numerator = $numerator->negated();
                $denominator = $denominator->negated();
            }
        }

        if ($simplify) {
            $gcd = $numerator->gcd($denominator);

            $numerator = $numerator->quotient($gcd);
            $denominator = $denominator->quotient($gcd);
        }

        $this->numerator = $numerator;
        $this->denominator = $denominator;
    }

    /**
     * Creates a BigRational out of a numerator and a denominator.
     *
     * If the denominator is negative, the signs of both the numerator and the denominator
     * will be inverted to ensure that the denominator is always positive.
     *
     * @param BigNumber|int|string $numerator   The numerator. Must be convertible to a BigInteger.
     * @param BigNumber|int|string $denominator The denominator. Must be convertible to a BigInteger.
     *
     * @throws NumberFormatException      If an argument does not represent a valid number.
     * @throws RoundingNecessaryException If an argument represents a non-integer number.
     * @throws DivisionByZeroException    If the denominator is zero.
     *
     * @pure
     */
    public static function ofFraction(
        BigNumber|int|string $numerator,
        BigNumber|int|string $denominator,
    ): BigRational {
        $numerator = BigInteger::of($numerator);
        $denominator = BigInteger::of($denominator);

        return new BigRational($numerator, $denominator, true, true);
    }

    /**
     * Returns a BigRational representing zero.
     *
     * @pure
     */
    public static function zero(): BigRational
    {
        /** @var BigRational|null $zero */
        static $zero;

        if ($zero === null) {
            $zero = new BigRational(BigInteger::zero(), BigInteger::one(), false, false);
        }

        return $zero;
    }

    /**
     * Returns a BigRational representing one.
     *
     * @pure
     */
    public static function one(): BigRational
    {
        /** @var BigRational|null $one */
        static $one;

        if ($one === null) {
            $one = new BigRational(BigInteger::one(), BigInteger::one(), false, false);
        }

        return $one;
    }

    /**
     * Returns a BigRational representing ten.
     *
     * @pure
     */
    public static function ten(): BigRational
    {
        /** @var BigRational|null $ten */
        static $ten;

        if ($ten === null) {
            $ten = new BigRational(BigInteger::ten(), BigInteger::one(), false, false);
        }

        return $ten;
    }

    /**
     * @pure
     */
    public function getNumerator(): BigInteger
    {
        return $this->numerator;
    }

    /**
     * @pure
     */
    public function getDenominator(): BigInteger
    {
        return $this->denominator;
    }

    /**
     * Returns the integral part of this rational number.
     *
     * Examples:
     *
     * - `7/3` returns `2` (since 7/3 = 2 + 1/3)
     * - `-7/3` returns `-2` (since -7/3 = -2 + (-1/3))
     *
     * The following identity holds: `$r->isEqualTo($r->getFractionalPart()->plus($r->getIntegralPart()))`.
     *
     * @pure
     */
    public function getIntegralPart(): BigInteger
    {
        return $this->numerator->quotient($this->denominator);
    }

    /**
     * Returns the fractional part of this rational number.
     *
     * Examples:
     *
     * - `7/3` returns `1/3` (since 7/3 = 2 + 1/3)
     * - `-7/3` returns `-1/3` (since -7/3 = -2 + (-1/3))
     *
     * The following identity holds: `$r->isEqualTo($r->getFractionalPart()->plus($r->getIntegralPart()))`.
     *
     * @pure
     */
    public function getFractionalPart(): BigRational
    {
        return new BigRational($this->numerator->remainder($this->denominator), $this->denominator, false, false);
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * @param BigNumber|int|string $that The number to add.
     *
     * @throws MathException If the number is not valid.
     *
     * @pure
     */
    public function plus(BigNumber|int|string $that): BigRational
    {
        $that = BigRational::of($that);

        $numerator = $this->numerator->multipliedBy($that->denominator);
        $numerator = $numerator->plus($that->numerator->multipliedBy($this->denominator));
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new BigRational($numerator, $denominator, false, true);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * @param BigNumber|int|string $that The number to subtract.
     *
     * @throws MathException If the number is not valid.
     *
     * @pure
     */
    public function minus(BigNumber|int|string $that): BigRational
    {
        $that = BigRational::of($that);

        $numerator = $this->numerator->multipliedBy($that->denominator);
        $numerator = $numerator->minus($that->numerator->multipliedBy($this->denominator));
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new BigRational($numerator, $denominator, false, true);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * @param BigNumber|int|string $that The multiplier.
     *
     * @throws MathException If the multiplier is not a valid number.
     *
     * @pure
     */
    public function multipliedBy(BigNumber|int|string $that): BigRational
    {
        $that = BigRational::of($that);

        $numerator = $this->numerator->multipliedBy($that->numerator);
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new BigRational($numerator, $denominator, false, true);
    }

    /**
     * Returns the result of the division of this number by the given one.
     *
     * @param BigNumber|int|string $that The divisor.
     *
     * @throws MathException If the divisor is not a valid number, or is zero.
     *
     * @pure
     */
    public function dividedBy(BigNumber|int|string $that): BigRational
    {
        $that = BigRational::of($that);

        $numerator = $this->numerator->multipliedBy($that->denominator);
        $denominator = $this->denominator->multipliedBy($that->numerator);

        return new BigRational($numerator, $denominator, true, true);
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * @throws InvalidArgumentException If the exponent is not in the range 0 to 1,000,000.
     *
     * @pure
     */
    public function power(int $exponent): BigRational
    {
        if ($exponent === 0) {
            return BigRational::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        return new BigRational(
            $this->numerator->power($exponent),
            $this->denominator->power($exponent),
            false,
            false,
        );
    }

    /**
     * Limits (clamps) this number between the given minimum and maximum values.
     *
     * If the number is lower than $min, returns a copy of $min.
     * If the number is greater than $max, returns a copy of $max.
     * Otherwise, returns this number unchanged.
     *
     * @param BigNumber|int|string $min The minimum. Must be convertible to a BigRational.
     * @param BigNumber|int|string $max The maximum. Must be convertible to a BigRational.
     *
     * @throws MathException            If min/max are not convertible to a BigRational.
     * @throws InvalidArgumentException If min is greater than max.
     */
    public function clamp(BigNumber|int|string $min, BigNumber|int|string $max): BigRational
    {
        $min = BigRational::of($min);
        $max = BigRational::of($max);

        if ($min->isGreaterThan($max)) {
            throw new InvalidArgumentException('Minimum value must be less than or equal to maximum value.');
        }

        if ($this->isLessThan($min)) {
            return $min;
        } elseif ($this->isGreaterThan($max)) {
            return $max;
        }

        return $this;
    }

    /**
     * Returns the reciprocal of this BigRational.
     *
     * The reciprocal has the numerator and denominator swapped.
     *
     * @throws DivisionByZeroException If the numerator is zero.
     *
     * @pure
     */
    public function reciprocal(): BigRational
    {
        return new BigRational($this->denominator, $this->numerator, true, false);
    }

    /**
     * Returns the absolute value of this BigRational.
     *
     * @pure
     */
    public function abs(): BigRational
    {
        return new BigRational($this->numerator->abs(), $this->denominator, false, false);
    }

    /**
     * Returns the negated value of this BigRational.
     *
     * @pure
     */
    public function negated(): BigRational
    {
        return new BigRational($this->numerator->negated(), $this->denominator, false, false);
    }

    /**
     * Returns the simplified value of this BigRational.
     *
     * @deprecated Since 0.15, this is a no-op. BigRational numbers are always in their simplest form.
     */
    public function simplified(): BigRational
    {
        trigger_error(
            'BigRational::simplified() is a no-op since 0.15, and will be removed in 0.16. BigRational numbers are now always simplified to lowest terms.',
            E_USER_DEPRECATED,
        );

        return $this;
    }

    #[Override]
    public function compareTo(BigNumber|int|string $that): int
    {
        return $this->minus($that)->getSign();
    }

    #[Override]
    public function getSign(): int
    {
        return $this->numerator->getSign();
    }

    #[Override]
    public function toBigInteger(): BigInteger
    {
        if (! $this->denominator->isEqualTo(1)) {
            throw new RoundingNecessaryException('This rational number cannot be represented as an integer value without rounding.');
        }

        return $this->numerator;
    }

    #[Override]
    public function toBigDecimal(): BigDecimal
    {
        return $this->numerator->toBigDecimal()->dividedByExact($this->denominator);
    }

    #[Override]
    public function toBigRational(): BigRational
    {
        return $this;
    }

    #[Override]
    public function toScale(int $scale, RoundingMode $roundingMode = RoundingMode::Unnecessary): BigDecimal
    {
        return $this->numerator->toBigDecimal()->dividedBy($this->denominator, $scale, $roundingMode);
    }

    #[Override]
    public function toInt(): int
    {
        return $this->toBigInteger()->toInt();
    }

    #[Override]
    public function toFloat(): float
    {
        return $this->numerator->toFloat() / $this->denominator->toFloat();
    }

    #[Override]
    public function toString(): string
    {
        $numerator = $this->numerator->toString();
        $denominator = $this->denominator->toString();

        if ($denominator === '1') {
            return $numerator;
        }

        return $numerator . '/' . $denominator;
    }

    /**
     * Returns the decimal representation of this rational number, with repeating decimals in parentheses.
     *
     * Examples:
     *
     * - `10/3` returns `3.(3)`
     * - `171/70` returns `2.4(428571)`
     * - `1/2` returns `0.5`
     *
     * @pure
     */
    public function toRepeatingDecimalString(): string
    {
        if ($this->numerator->isZero()) {
            return '0';
        }

        $sign = $this->numerator->isNegative() ? '-' : '';
        $numerator = $this->numerator->abs();
        $denominator = $this->denominator;

        $integral = $numerator->quotient($denominator);
        $remainder = $numerator->remainder($denominator);

        $integralString = $integral->toString();

        if ($remainder->isZero()) {
            return $sign . $integralString;
        }

        $digits = '';
        $remainderPositions = [];
        $index = 0;

        while (! $remainder->isZero()) {
            $remainderAsString = $remainder->toString();

            if (isset($remainderPositions[$remainderAsString])) {
                $repeatIndex = $remainderPositions[$remainderAsString];
                $nonRepeating = substr($digits, 0, $repeatIndex);
                $repeating = substr($digits, $repeatIndex);

                return $sign . $integralString . '.' . $nonRepeating . '(' . $repeating . ')';
            }

            $remainderPositions[$remainderAsString] = $index;
            $remainder = $remainder->multipliedBy(10);

            $digits .= $remainder->quotient($denominator)->toString();
            $remainder = $remainder->remainder($denominator);
            $index++;
        }

        return $sign . $integralString . '.' . $digits;
    }

    /**
     * This method is required for serializing the object and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return array{numerator: BigInteger, denominator: BigInteger}
     */
    public function __serialize(): array
    {
        return ['numerator' => $this->numerator, 'denominator' => $this->denominator];
    }

    /**
     * This method is only here to allow unserializing the object and cannot be accessed directly.
     *
     * @internal
     *
     * @param array{numerator: BigInteger, denominator: BigInteger} $data
     *
     * @throws LogicException
     */
    public function __unserialize(array $data): void
    {
        /** @phpstan-ignore isset.initializedProperty */
        if (isset($this->numerator)) {
            throw new LogicException('__unserialize() is an internal function, it must not be called directly.');
        }

        /** @phpstan-ignore deadCode.unreachable */
        $this->numerator = $data['numerator'];
        $this->denominator = $data['denominator'];
    }

    #[Override]
    protected static function from(BigNumber $number): static
    {
        return $number->toBigRational();
    }
}
