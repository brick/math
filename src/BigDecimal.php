<?php

declare(strict_types=1);

namespace Brick\Math;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Internal\Calculator;
use InvalidArgumentException;
use LogicException;

/**
 * Immutable, arbitrary-precision signed decimal numbers.
 *
 * @psalm-immutable
 */
final class BigDecimal extends BigNumber
{
    /**
     * The unscaled value of this decimal number.
     *
     * This is a string of digits with an optional leading minus sign. No leading zero must be present. No leading minus
     * sign must be present if the value is 0.
     */
    private string $value;

    /**
     * The scale (number of digits after the decimal point) of this decimal number.
     *
     * This must be zero or more.
     */
    private int $scale;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param string $value The unscaled value, validated.
     * @param int    $scale The scale, validated.
     */
    protected function __construct(string $value, int $scale = 0)
    {
        $this->value = $value;
        $this->scale = $scale;
    }

    public function __toString(): string
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return \substr($value, 0, -$this->scale) . '.' . \substr($value, -$this->scale);
    }

    /**
     * This method is required for serializing the object and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return array{value: string, scale: int}
     */
    public function __serialize(): array
    {
        return [
            'value' => $this->value,
            'scale' => $this->scale,
        ];
    }

    /**
     * This method is only here to allow unserializing the object and cannot be accessed directly.
     *
     * @internal
     * @psalm-suppress RedundantPropertyInitializationCheck
     *
     * @param array{value: string, scale: int} $data
     */
    public function __unserialize(array $data): void
    {
        if (isset($this->value)) {
            throw new LogicException('__unserialize() is an internal function, it must not be called directly.');
        }

        $this->value = $data['value'];
        $this->scale = $data['scale'];
    }

    /**
     * Creates a BigDecimal of the given value.
     *
     * @param BigNumber|int|float|string $value
     *
     * @return BigDecimal
     *
     * @psalm-pure
     */
    public static function of($value): BigNumber
    {
        return parent::of($value)->toBigDecimal();
    }

    /**
     * Creates a BigDecimal from an unscaled value and a scale.
     *
     * Example: `(12345, 3)` will result in the BigDecimal `12.345`.
     *
     * @param BigNumber|int|float|string $value The unscaled value. Must be convertible to a BigInteger.
     * @param int                        $scale The scale of the number, positive or zero.
     *
     * @psalm-pure
     */
    public static function ofUnscaledValue($value, int $scale = 0): self
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('The scale cannot be negative.');
        }

        return new self((string) BigInteger::of($value), $scale);
    }

    /**
     * Returns a BigDecimal representing zero, with a scale of zero.
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
            $zero = new self('0');
        }

        return $zero;
    }

    /**
     * Returns a BigDecimal representing one, with a scale of zero.
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
            $one = new self('1');
        }

        return $one;
    }

    /**
     * Returns a BigDecimal representing ten, with a scale of zero.
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
            $ten = new self('10');
        }

        return $ten;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The number to add. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     */
    public function plus($that): self
    {
        $that = self::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        if ($this->value === '0' && $this->scale <= $that->scale) {
            return $that;
        }

        [$a, $b] = $this->scaleValues($this, $that);

        $value = Calculator::get()->add($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new self($value, $scale);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The number to subtract. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     */
    public function minus($that): self
    {
        $that = self::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        [$a, $b] = $this->scaleValues($this, $that);

        $value = Calculator::get()->sub($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new self($value, $scale);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * The result has a scale of `$this->scale + $that->scale`.
     *
     * @param BigNumber|int|float|string $that The multiplier. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     */
    public function multipliedBy($that): self
    {
        $that = self::of($that);

        if ($that->value === '1' && $that->scale === 0) {
            return $this;
        }

        if ($this->value === '1' && $this->scale === 0) {
            return $that;
        }

        $value = Calculator::get()->mul($this->value, $that->value);
        $scale = $this->scale + $that->scale;

        return new self($value, $scale);
    }

    /**
     * Returns the result of the division of this number by the given one, at the given scale.
     *
     * @param BigNumber|int|float|string $that         The divisor.
     * @param int|null                   $scale        The desired scale, or null to use the scale of this number.
     * @param int                        $roundingMode An optional rounding mode.
     */
    public function dividedBy($that, ?int $scale = null, int $roundingMode = RoundingMode::UNNECESSARY): self
    {
        $that = self::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        if ($scale === null) {
            $scale = $this->scale;
        } elseif ($scale < 0) {
            throw new InvalidArgumentException('Scale cannot be negative.');
        }

        if ($that->value === '1' && $that->scale === 0 && $scale === $this->scale) {
            return $this;
        }

        $p = $this->valueWithMinScale($that->scale + $scale);
        $q = $that->valueWithMinScale($this->scale - $scale);

        $result = Calculator::get()->divRound($p, $q, $roundingMode);

        return new self($result, $scale);
    }

    /**
     * Returns the exact result of the division of this number by the given one.
     *
     * The scale of the result is automatically calculated to fit all the fraction digits.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     */
    public function exactlyDividedBy($that): self
    {
        $that = self::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        [, $b] = $this->scaleValues($this, $that);

        $d = \rtrim($b, '0');
        $scale = \strlen($b) - \strlen($d);

        $calculator = Calculator::get();

        foreach ([5, 2] as $prime) {
            for (; ;) {
                $lastDigit = (int) $d[-1];

                if ($lastDigit % $prime !== 0) {
                    break;
                }

                $d = $calculator->divQ($d, (string) $prime);
                $scale++;
            }
        }

        return $this->dividedBy($that, $scale)
            ->stripTrailingZeros();
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * The result has a scale of `$this->scale * $exponent`.
     *
     * @param int $exponent The exponent.
     *
     * @return BigDecimal The result.
     */
    public function power(int $exponent): self
    {
        if ($exponent === 0) {
            return self::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw new InvalidArgumentException(\sprintf(
                'The exponent %d is not in the range 0 to %d.',
                $exponent,
                Calculator::MAX_POWER
            ));
        }

        return new self(Calculator::get()->pow($this->value, $exponent), $this->scale * $exponent);
    }

    /**
     * Returns the quotient of the division of this number by this given one.
     *
     * The quotient has a scale of `0`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The quotient.
     */
    public function quotient($that): self
    {
        $that = self::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $quotient = Calculator::get()->divQ($p, $q);

        return new self($quotient, 0);
    }

    /**
     * Returns the remainder of the division of this number by this given one.
     *
     * The remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The remainder.
     */
    public function remainder($that): self
    {
        $that = self::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $remainder = Calculator::get()->divR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new self($remainder, $scale);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * The quotient has a scale of `0`, and the remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal[] An array containing the quotient and the remainder.
     */
    public function quotientAndRemainder($that): array
    {
        $that = self::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        [$quotient, $remainder] = Calculator::get()->divQR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        $quotient = new self($quotient, 0);
        $remainder = new self($remainder, $scale);

        return [$quotient, $remainder];
    }

    /**
     * Returns the square root of this number, rounded down to the given number of decimals.
     */
    public function sqrt(int $scale): self
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale cannot be negative.');
        }

        if ($this->value === '0') {
            return new self('0', $scale);
        }

        if ($this->value[0] === '-') {
            throw new NegativeNumberException('Cannot calculate the square root of a negative number.');
        }

        $value = $this->value;
        $addDigits = 2 * $scale - $this->scale;

        if ($addDigits > 0) {
            // add zeros
            $value .= \str_repeat('0', $addDigits);
        } elseif ($addDigits < 0) {
            // trim digits
            if (-$addDigits >= \strlen($this->value)) {
                // requesting a scale too low, will always yield a zero result
                return new self('0', $scale);
            }

            $value = \substr($value, 0, $addDigits);
        }

        $value = Calculator::get()->sqrt($value);

        return new self($value, $scale);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the left.
     */
    public function withPointMovedLeft(int $n): self
    {
        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedRight(-$n);
        }

        return new self($this->value, $this->scale + $n);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the right.
     */
    public function withPointMovedRight(int $n): self
    {
        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedLeft(-$n);
        }

        $value = $this->value;
        $scale = $this->scale - $n;

        if ($scale < 0) {
            if ($value !== '0') {
                $value .= \str_repeat('0', -$scale);
            }
            $scale = 0;
        }

        return new self($value, $scale);
    }

    /**
     * Returns a copy of this BigDecimal with any trailing zeros removed from the fractional part.
     */
    public function stripTrailingZeros(): self
    {
        if ($this->scale === 0) {
            return $this;
        }

        $trimmedValue = \rtrim($this->value, '0');

        if ($trimmedValue === '') {
            return self::zero();
        }

        $trimmableZeros = \strlen($this->value) - \strlen($trimmedValue);

        if ($trimmableZeros === 0) {
            return $this;
        }

        if ($trimmableZeros > $this->scale) {
            $trimmableZeros = $this->scale;
        }

        $value = \substr($this->value, 0, -$trimmableZeros);
        $scale = $this->scale - $trimmableZeros;

        return new self($value, $scale);
    }

    /**
     * Returns the absolute value of this number.
     */
    public function abs(): self
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the negated value of this number.
     */
    public function negated(): self
    {
        return new self(Calculator::get()->neg($this->value), $this->scale);
    }

    public function compareTo($that): int
    {
        $that = BigNumber::of($that);

        if ($that instanceof BigInteger) {
            $that = $that->toBigDecimal();
        }

        if ($that instanceof self) {
            [$a, $b] = $this->scaleValues($this, $that);

            return Calculator::get()->cmp($a, $b);
        }

        return -$that->compareTo($this);
    }

    public function getSign(): int
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    public function getUnscaledValue(): BigInteger
    {
        return BigInteger::create($this->value);
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * Returns a string representing the integral part of this decimal number.
     *
     * Example: `-123.456` => `-123`.
     */
    public function getIntegralPart(): string
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return \substr($value, 0, -$this->scale);
    }

    /**
     * Returns a string representing the fractional part of this decimal number.
     *
     * If the scale is zero, an empty string is returned.
     *
     * Examples: `-123.456` => '456', `123` => ''.
     */
    public function getFractionalPart(): string
    {
        if ($this->scale === 0) {
            return '';
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return \substr($value, -$this->scale);
    }

    /**
     * Returns whether this decimal number has a non-zero fractional part.
     */
    public function hasNonZeroFractionalPart(): bool
    {
        return $this->getFractionalPart() !== \str_repeat('0', $this->scale);
    }

    public function toBigInteger(): BigInteger
    {
        $zeroScaleDecimal = $this->scale === 0 ? $this : $this->dividedBy(1, 0);

        return BigInteger::create($zeroScaleDecimal->value);
    }

    public function toBigDecimal(): self
    {
        return $this;
    }

    public function toBigRational(): BigRational
    {
        $numerator = BigInteger::create($this->value);
        $denominator = BigInteger::create('1' . \str_repeat('0', $this->scale));

        return BigRational::create($numerator, $denominator, false);
    }

    public function toScale(int $scale, int $roundingMode = RoundingMode::UNNECESSARY): self
    {
        if ($scale === $this->scale) {
            return $this;
        }

        return $this->dividedBy(self::one(), $scale, $roundingMode);
    }

    public function toInt(): int
    {
        return $this->toBigInteger()
            ->toInt();
    }

    public function toFloat(): float
    {
        return (float) (string) $this;
    }

    /**
     * This method is required by interface Serializable and SHOULD NOT be accessed directly.
     *
     * @internal
     */
    public function serialize(): string
    {
        return $this->value . ':' . $this->scale;
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
        if (isset($this->value)) {
            throw new LogicException('unserialize() is an internal function, it must not be called directly.');
        }

        [$value, $scale] = \explode(':', $value);

        $this->value = $value;
        $this->scale = (int) $scale;
    }

    /**
     * Puts the internal values of the given decimal numbers on the same scale.
     *
     * @param BigDecimal $x The first decimal number.
     * @param BigDecimal $y The second decimal number.
     *
     * @return array{string, string} The scaled integer values of $x and $y.
     */
    private function scaleValues(self $x, self $y): array
    {
        $a = $x->value;
        $b = $y->value;

        if ($b !== '0' && $x->scale > $y->scale) {
            $b .= \str_repeat('0', $x->scale - $y->scale);
        } elseif ($a !== '0' && $x->scale < $y->scale) {
            $a .= \str_repeat('0', $y->scale - $x->scale);
        }

        return [$a, $b];
    }

    private function valueWithMinScale(int $scale): string
    {
        $value = $this->value;

        if ($this->value !== '0' && $scale > $this->scale) {
            $value .= \str_repeat('0', $scale - $this->scale);
        }

        return $value;
    }

    /**
     * Adds leading zeros if necessary to the unscaled value to represent the full decimal number.
     */
    private function getUnscaledValueWithLeadingZeros(): string
    {
        $value = $this->value;
        $targetLength = $this->scale + 1;
        $negative = ($value[0] === '-');
        $length = \strlen($value);

        if ($negative) {
            $length--;
        }

        if ($length >= $targetLength) {
            return $this->value;
        }

        if ($negative) {
            $value = \substr($value, 1);
        }

        $value = \str_pad($value, $targetLength, '0', STR_PAD_LEFT);

        if ($negative) {
            $value = '-' . $value;
        }

        return $value;
    }
}
