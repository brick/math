<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\RoundingNecessaryException;
use LogicException;

/**
 * An arbitrarily large rational number.
 *
 * This class is immutable.
 *
 * @psalm-immutable
 */
final class BigRational extends BigNumber
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
     */
    protected function __construct(BigInteger $numerator, BigInteger $denominator, bool $checkDenominator)
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

        $this->numerator = $numerator;
        $this->denominator = $denominator;
    }

    public function __toString(): string
    {
        $numerator = (string) $this->numerator;
        $denominator = (string) $this->denominator;

        if ($denominator === '1') {
            return $numerator;
        }

        return $this->numerator . '/' . $this->denominator;
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
        return [
            'numerator' => $this->numerator,
            'denominator' => $this->denominator,
        ];
    }

    /**
     * This method is only here to allow unserializing the object and cannot be accessed directly.
     *
     * @internal
     * @psalm-suppress RedundantPropertyInitializationCheck
     *
     * @param array{numerator: BigInteger, denominator: BigInteger} $data
     */
    public function __unserialize(array $data): void
    {
        if (isset($this->numerator)) {
            throw new LogicException('__unserialize() is an internal function, it must not be called directly.');
        }

        $this->numerator = $data['numerator'];
        $this->denominator = $data['denominator'];
    }

    /**
     * Creates a BigRational of the given value.
     *
     * @param BigNumber|int|float|string $value
     *
     * @return BigRational
     *
     * @psalm-pure
     */
    public static function of($value): BigNumber
    {
        return parent::of($value)->toBigRational();
    }

    /**
     * Creates a BigRational out of a numerator and a denominator.
     *
     * If the denominator is negative, the signs of both the numerator and the denominator will be inverted to ensure
     * that the denominator is always positive.
     *
     * @param BigNumber|int|float|string $numerator   The numerator. Must be convertible to a BigInteger.
     * @param BigNumber|int|float|string $denominator The denominator. Must be convertible to a BigInteger.
     *
     * @psalm-pure
     */
    public static function nd($numerator, $denominator): self
    {
        $numerator = BigInteger::of($numerator);
        $denominator = BigInteger::of($denominator);

        return new self($numerator, $denominator, true);
    }

    /**
     * Returns a BigRational representing zero.
     *
     * @psalm-pure
     */
    public static function zero(): self
    {
        /**
         * @psalm-suppress ImpureStaticVariable
         */
        static $zero;

        if ($zero === null) {
            $zero = new self(BigInteger::zero(), BigInteger::one(), false);
        }

        return $zero;
    }

    /**
     * Returns a BigRational representing one.
     *
     * @psalm-pure
     */
    public static function one(): self
    {
        /**
         * @psalm-suppress ImpureStaticVariable
         */
        static $one;

        if ($one === null) {
            $one = new self(BigInteger::one(), BigInteger::one(), false);
        }

        return $one;
    }

    /**
     * Returns a BigRational representing ten.
     *
     * @psalm-pure
     */
    public static function ten(): self
    {
        /**
         * @psalm-suppress ImpureStaticVariable
         */
        static $ten;

        if ($ten === null) {
            $ten = new self(BigInteger::ten(), BigInteger::one(), false);
        }

        return $ten;
    }

    public function getNumerator(): BigInteger
    {
        return $this->numerator;
    }

    public function getDenominator(): BigInteger
    {
        return $this->denominator;
    }

    /**
     * Returns the quotient of the division of the numerator by the denominator.
     */
    public function quotient(): BigInteger
    {
        return $this->numerator->quotient($this->denominator);
    }

    /**
     * Returns the remainder of the division of the numerator by the denominator.
     */
    public function remainder(): BigInteger
    {
        return $this->numerator->remainder($this->denominator);
    }

    /**
     * Returns the quotient and remainder of the division of the numerator by the denominator.
     *
     * @return BigInteger[]
     */
    public function quotientAndRemainder(): array
    {
        return $this->numerator->quotientAndRemainder($this->denominator);
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * @param BigNumber|int|float|string $that The number to add.
     *
     * @return BigRational The result.
     */
    public function plus($that): self
    {
        $that = self::of($that);

        $numerator = $this->numerator->multipliedBy($that->denominator);
        $numerator = $numerator->plus($that->numerator->multipliedBy($this->denominator));
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new self($numerator, $denominator, false);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * @param BigNumber|int|float|string $that The number to subtract.
     *
     * @return BigRational The result.
     */
    public function minus($that): self
    {
        $that = self::of($that);

        $numerator = $this->numerator->multipliedBy($that->denominator);
        $numerator = $numerator->minus($that->numerator->multipliedBy($this->denominator));
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new self($numerator, $denominator, false);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * @param BigNumber|int|float|string $that The multiplier.
     *
     * @return BigRational The result.
     */
    public function multipliedBy($that): self
    {
        $that = self::of($that);

        $numerator = $this->numerator->multipliedBy($that->numerator);
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new self($numerator, $denominator, false);
    }

    /**
     * Returns the result of the division of this number by the given one.
     *
     * @param BigNumber|int|float|string $that The divisor.
     *
     * @return BigRational The result.
     */
    public function dividedBy($that): self
    {
        $that = self::of($that);

        $numerator = $this->numerator->multipliedBy($that->denominator);
        $denominator = $this->denominator->multipliedBy($that->numerator);

        return new self($numerator, $denominator, true);
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * @param int $exponent The exponent.
     *
     * @return BigRational The result.
     */
    public function power(int $exponent): self
    {
        if ($exponent === 0) {
            $one = BigInteger::one();

            return new self($one, $one, false);
        }

        if ($exponent === 1) {
            return $this;
        }

        return new self($this->numerator->power($exponent), $this->denominator->power($exponent), false);
    }

    /**
     * Returns the reciprocal of this BigRational.
     *
     * The reciprocal has the numerator and denominator swapped.
     */
    public function reciprocal(): self
    {
        return new self($this->denominator, $this->numerator, true);
    }

    /**
     * Returns the absolute value of this BigRational.
     */
    public function abs(): self
    {
        return new self($this->numerator->abs(), $this->denominator, false);
    }

    /**
     * Returns the negated value of this BigRational.
     */
    public function negated(): self
    {
        return new self($this->numerator->negated(), $this->denominator, false);
    }

    /**
     * Returns the simplified value of this BigRational.
     */
    public function simplified(): self
    {
        $gcd = $this->numerator->gcd($this->denominator);

        $numerator = $this->numerator->quotient($gcd);
        $denominator = $this->denominator->quotient($gcd);

        return new self($numerator, $denominator, false);
    }

    public function compareTo($that): int
    {
        return $this->minus($that)
            ->getSign();
    }

    public function getSign(): int
    {
        return $this->numerator->getSign();
    }

    public function toBigInteger(): BigInteger
    {
        $simplified = $this->simplified();

        if (! $simplified->denominator->isEqualTo(1)) {
            throw new RoundingNecessaryException(
                'This rational number cannot be represented as an integer value without rounding.'
            );
        }

        return $simplified->numerator;
    }

    public function toBigDecimal(): BigDecimal
    {
        return $this->numerator->toBigDecimal()
            ->exactlyDividedBy($this->denominator);
    }

    public function toBigRational(): self
    {
        return $this;
    }

    public function toScale(int $scale, int $roundingMode = RoundingMode::UNNECESSARY): BigDecimal
    {
        return $this->numerator->toBigDecimal()
            ->dividedBy($this->denominator, $scale, $roundingMode);
    }

    public function toInt(): int
    {
        return $this->toBigInteger()
            ->toInt();
    }

    public function toFloat(): float
    {
        return $this->numerator->toFloat() / $this->denominator->toFloat();
    }

    /**
     * This method is required by interface Serializable and SHOULD NOT be accessed directly.
     *
     * @internal
     */
    public function serialize(): string
    {
        return $this->numerator . '/' . $this->denominator;
    }

    /**
     * This method is only here to implement interface Serializable and cannot be accessed directly.
     *
     * @internal
     * @psalm-suppress RedundantPropertyInitializationCheck
     *
     * @param string $value
     */
    public function unserialize($value): void
    {
        if (isset($this->numerator)) {
            throw new LogicException('unserialize() is an internal function, it must not be called directly.');
        }

        [$numerator, $denominator] = \explode('/', $value);

        $this->numerator = BigInteger::of($numerator);
        $this->denominator = BigInteger::of($denominator);
    }
}
