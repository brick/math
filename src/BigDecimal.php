<?php

namespace Brick\Math;

use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Internal\Calculator;

/**
 * Immutable, arbitrary-precision signed decimal numbers.
 */
final class BigDecimal extends BigNumber
{
    /**
     * The unscaled value of this decimal number.
     *
     * This is a string of digits with an optional leading minus sign.
     * No leading zero must be present.
     * No leading minus sign must be present if the value is 0.
     *
     * @var string
     */
    private $value;

    /**
     * The scale (number of digits after the decimal point) of this decimal number.
     *
     * This must be zero or more.
     *
     * @var int
     */
    private $scale;

    /**
     * Protected constructor. Use a factory method to obtain an instance.
     *
     * @param string $value The unscaled value, validated.
     * @param int    $scale The scale, validated as a positive or zero integer.
     */
    protected function __construct($value, $scale = 0)
    {
        $this->value = $value;
        $this->scale = $scale;
    }

    /**
     * Creates a BigDecimal of the given value.
     *
     * @param BigNumber|number|string $value
     *
     * @return BigDecimal
     *
     * @throws ArithmeticException If the value cannot be converted to a BigDecimal.
     */
    public static function of($value)
    {
        return parent::of($value)->toBigDecimal();
    }

    /**
     * Creates a BigDecimal from an unscaled value and a scale.
     *
     * Example: `(12345, 3)` will result in the BigDecimal `12.345`.
     *
     * @param BigNumber|number|string $value The unscaled value. Must be convertible to a BigInteger.
     * @param int                     $scale The scale of the number, positive or zero.
     *
     * @return BigDecimal
     *
     * @throws \InvalidArgumentException If the scale is negative.
     */
    public static function ofUnscaledValue($value, $scale = 0)
    {
        $scale = (int) $scale;

        if ($scale < 0) {
            throw new \InvalidArgumentException('The scale cannot be negative.');
        }

        return new BigDecimal((string) BigInteger::of($value), $scale);
    }

    /**
     * Returns a BigDecimal representing zero, with a scale of zero.
     *
     * @return BigDecimal
     */
    public static function zero()
    {
        static $zero = null;

        if ($zero === null) {
            $zero = new BigDecimal('0');
        }

        return $zero;
    }

    /**
     * Returns a BigDecimal representing one, with a scale of zero.
     *
     * @return BigDecimal
     */
    public static function one()
    {
        static $one = null;

        if ($one === null) {
            $one = new BigDecimal('1');
        }

        return $one;
    }

    /**
     * Returns a BigDecimal representing ten, with a scale of zero.
     *
     * @return BigDecimal
     */
    public static function ten()
    {
        static $ten = null;

        if ($ten === null) {
            $ten = new BigDecimal('10');
        }

        return $ten;
    }

    /**
     * Returns the sum of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|number|string $that The number to add. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     *
     * @throws ArithmeticException If the number is not valid, or is not convertible to a BigDecimal.
     */
    public function plus($that)
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        $this->scaleValues($this, $that, $a, $b);

        $value = Calculator::get()->add($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the difference of this number and the given one.
     *
     * The result has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|number|string $that The number to subtract. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     *
     * @throws ArithmeticException If the number is not valid, or is not convertible to a BigDecimal.
     */
    public function minus($that)
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0' && $that->scale <= $this->scale) {
            return $this;
        }

        $this->scaleValues($this, $that, $a, $b);

        $value = Calculator::get()->sub($a, $b);
        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the product of this number and the given one.
     *
     * The result has a scale of `$this->scale + $that->scale`.
     *
     * @param BigNumber|number|string $that The multiplier. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     *
     * @throws ArithmeticException If the multiplier is not a valid number, or is not convertible to a BigDecimal.
     */
    public function multipliedBy($that)
    {
        $that = BigDecimal::of($that);

        if ($that->value === '1' && $that->scale === 0) {
            return $this;
        }

        $value = Calculator::get()->mul($this->value, $that->value);
        $scale = $this->scale + $that->scale;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the result of the division of this number by the given one, at the given scale.
     *
     * @param BigNumber|number|string $that         The divisor.
     * @param int|null                $scale        The desired scale, or null to use the scale of this number.
     * @param int                     $roundingMode An optional rounding mode.
     *
     * @return BigDecimal
     *
     * @throws \InvalidArgumentException If the scale or rounding mode is invalid.
     * @throws ArithmeticException       If the number is invalid, is zero, or rounding was necessary.
     */
    public function dividedBy($that, $scale = null, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        if ($scale === null) {
            $scale = $this->scale;
        } else {
            $scale = (int) $scale;

            if ($scale < 0) {
                throw new \InvalidArgumentException('Scale cannot be negative.');
            }
        }

        if ($that->value === '1' && $that->scale === 0 && $scale === $this->scale) {
            return $this;
        }

        $p = $this->valueWithMinScale($that->scale + $scale);
        $q = $that->valueWithMinScale($this->scale - $scale);

        $result = Calculator::get()->divRound($p, $q, $roundingMode);

        return new BigDecimal($result, $scale);
    }

    /**
     * Returns the exact result of the division of this number by the given one.
     *
     * The scale of the result is automatically calculated to fit all the fraction digits.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The result.
     *
     * @throws ArithmeticException If the divisor is not a valid number, is not convertible to a BigDecimal, is zero,
     *                             or the result yields an infinite number of digits.
     */
    public function exactlyDividedBy($that)
    {
        $that = BigDecimal::of($that);

        if ($that->value === '0') {
            throw DivisionByZeroException::divisionByZero();
        }

        $this->scaleValues($this, $that, $a, $b);

        $d = rtrim($b, '0');
        $scale = strlen($b) - strlen($d);

        $calculator = Calculator::get();

        foreach ([5, 2] as $prime) {
            for (;;) {
                $lastDigit = (int) substr($d, -1);

                if ($lastDigit % $prime !== 0) {
                    break;
                }

                $d = $calculator->divQ($d, (string) $prime);
                $scale++;
            }
        }

        return $this->dividedBy($that, $scale)->stripTrailingZeros();
    }

    /**
     * Returns this number exponentiated to the given value.
     *
     * The result has a scale of `$this->scale * $exponent`.
     *
     * @param int $exponent The exponent.
     *
     * @return BigDecimal The result.
     *
     * @throws \InvalidArgumentException If the exponent is not in the range 0 to 1,000,000.
     */
    public function power($exponent)
    {
        $exponent = (int) $exponent;

        if ($exponent === 0) {
            return BigDecimal::one();
        }

        if ($exponent === 1) {
            return $this;
        }

        if ($exponent < 0 || $exponent > Calculator::MAX_POWER) {
            throw new \InvalidArgumentException(sprintf(
                'The exponent %d is not in the range 0 to %d.',
                $exponent,
                Calculator::MAX_POWER
            ));
        }

        return new BigDecimal(Calculator::get()->pow($this->value, $exponent), $this->scale * $exponent);
    }

    /**
     * Returns the quotient of the division of this number by this given one.
     *
     * The quotient has a scale of `0`.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The quotient.
     *
     * @throws ArithmeticException If the divisor is not a valid decimal number, or is zero.
     */
    public function quotient($that)
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $quotient = Calculator::get()->divQ($p, $q);

        return new BigDecimal($quotient, 0);
    }

    /**
     * Returns the remainder of the division of this number by this given one.
     *
     * The remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal The remainder.
     *
     * @throws ArithmeticException If the divisor is not a valid decimal number, or is zero.
     */
    public function remainder($that)
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        $remainder = Calculator::get()->divR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        return new BigDecimal($remainder, $scale);
    }

    /**
     * Returns the quotient and remainder of the division of this number by the given one.
     *
     * The quotient has a scale of `0`, and the remainder has a scale of `max($this->scale, $that->scale)`.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigDecimal.
     *
     * @return BigDecimal[] An array containing the quotient and the remainder.
     *
     * @throws ArithmeticException If the divisor is not a valid decimal number, or is zero.
     */
    public function quotientAndRemainder($that)
    {
        $that = BigDecimal::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $p = $this->valueWithMinScale($that->scale);
        $q = $that->valueWithMinScale($this->scale);

        list ($quotient, $remainder) = Calculator::get()->divQR($p, $q);

        $scale = $this->scale > $that->scale ? $this->scale : $that->scale;

        $quotient = new BigDecimal($quotient, 0);
        $remainder = new BigDecimal($remainder, $scale);

        return [$quotient, $remainder];
    }

    /**
     * Returns a BigDecimal with the current value and the specified scale.
     *
     * @deprecated Use `toScale()`.
     *
     * @param int $scale
     * @param int $roundingMode
     *
     * @return BigDecimal
     */
    public function withScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return $this->toScale($scale, $roundingMode);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the left.
     *
     * @param int $n
     *
     * @return BigDecimal
     */
    public function withPointMovedLeft($n)
    {
        $n = (int) $n;

        if ($n === 0) {
            return $this;
        }

        if ($n < 0) {
            return $this->withPointMovedRight(-$n);
        }

        return new BigDecimal($this->value, $this->scale + $n);
    }

    /**
     * Returns a copy of this BigDecimal with the decimal point moved $n places to the right.
     *
     * @param int $n
     *
     * @return BigDecimal
     */
    public function withPointMovedRight($n)
    {
        $n = (int) $n;

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
                $value .= str_repeat('0', -$scale);
            }
            $scale = 0;
        }

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns a copy of this BigDecimal with any trailing zeros removed from the fractional part.
     *
     * @return BigDecimal
     */
    public function stripTrailingZeros()
    {
        if ($this->scale === 0) {
            return $this;
        }

        $trimmedValue = rtrim($this->value, '0');

        if ($trimmedValue === '') {
            return BigDecimal::zero();
        }

        $trimmableZeros = strlen($this->value) - strlen($trimmedValue);

        if ($trimmableZeros === 0) {
            return $this;
        }

        if ($trimmableZeros > $this->scale) {
            $trimmableZeros = $this->scale;
        }

        $value = substr($this->value, 0, -$trimmableZeros);
        $scale = $this->scale - $trimmableZeros;

        return new BigDecimal($value, $scale);
    }

    /**
     * Returns the absolute value of this number.
     *
     * @return BigDecimal
     */
    public function abs()
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Returns the negated value of this number.
     *
     * @return BigDecimal
     */
    public function negated()
    {
        return new BigDecimal(Calculator::get()->neg($this->value), $this->scale);
    }

    /**
     * {@inheritdoc}
     */
    public function compareTo($that)
    {
        $that = BigNumber::of($that);

        if ($that instanceof BigInteger) {
            $that = $that->toBigDecimal();
        }

        if ($that instanceof BigDecimal) {
            $this->scaleValues($this, $that, $a, $b);

            return Calculator::get()->cmp($a, $b);
        }

        return - $that->compareTo($this);
    }

    /**
     * {@inheritdoc}
     */
    public function sign()
    {
        return ($this->value === '0') ? 0 : (($this->value[0] === '-') ? -1 : 1);
    }

    /**
     * @return string
     */
    public function unscaledValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function scale()
    {
        return $this->scale;
    }

    /**
     * Returns a string representing the integral part of this decimal number.
     *
     * Example: `-123.456` => `-123`.
     *
     * @return string
     */
    public function integral()
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return substr($value, 0, -$this->scale);
    }

    /**
     * Returns a string representing the fractional part of this decimal number.
     *
     * If the scale is zero, an empty string is returned.
     *
     * Examples: `-123.456` => '456', `123` => ''.
     *
     * @return string
     */
    public function fraction()
    {
        if ($this->scale === 0) {
            return '';
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return substr($value, -$this->scale);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigInteger()
    {
        if ($this->scale === 0) {
            $zeroScaleDecimal = $this;
        } else {
            $zeroScaleDecimal = $this->dividedBy(1, 0);
        }

        return BigInteger::create($zeroScaleDecimal->value);
    }

    /**
     * {@inheritdoc}
     */
    public function toBigDecimal()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toBigRational()
    {
        $numerator = BigInteger::create($this->value);
        $denominator = BigInteger::create('1' . str_repeat('0', $this->scale));

        return BigRational::create($numerator, $denominator, false);
    }

    /**
     * {@inheritdoc}
     */
    public function toScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $scale = (int) $scale;

        if ($scale === $this->scale) {
            return $this;
        }

        return $this->dividedBy(BigDecimal::one(), $scale, $roundingMode);
    }

    /**
     * {@inheritdoc}
     */
    public function toInteger()
    {
        return $this->toBigInteger()->toInteger();
    }

    /**
     * {@inheritdoc}
     */
    public function toFloat()
    {
        return (float) (string) $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if ($this->scale === 0) {
            return $this->value;
        }

        $value = $this->getUnscaledValueWithLeadingZeros();

        return substr($value, 0, -$this->scale) . '.' . substr($value, -$this->scale);
    }

    /**
     * This method is required by interface Serializable and SHOULD NOT be accessed directly.
     *
     * @internal
     *
     * @return string
     */
    public function serialize()
    {
        return $this->value . ':' . $this->scale;
    }

    /**
     * This method is required by interface Serializable and MUST NOT be accessed directly.
     *
     * @internal
     *
     * @param string $value
     *
     * @return void
     */
    public function unserialize($value)
    {
        if ($this->value !== null || $this->scale !== null) {
            throw new \LogicException('unserialize() is an internal function, it must not be called directly.');
        }

        list ($value, $scale) = explode(':', $value);

        $this->value = $value;
        $this->scale = (int) $scale;
    }

    /**
     * Puts the internal values of the given decimal numbers on the same scale.
     *
     * @param BigDecimal $x The first decimal number.
     * @param BigDecimal $y The second decimal number.
     * @param string     $a A variable to store the scaled integer value of $x.
     * @param string     $b A variable to store the scaled integer value of $y.
     *
     * @return void
     */
    private function scaleValues(BigDecimal $x, BigDecimal $y, & $a, & $b)
    {
        $a = $x->value;
        $b = $y->value;

        if ($b !== '0' && $x->scale > $y->scale) {
            $b .= str_repeat('0', $x->scale - $y->scale);
        } elseif ($a !== '0' && $x->scale < $y->scale) {
            $a .= str_repeat('0', $y->scale - $x->scale);
        }
    }

    /**
     * @param int $scale
     *
     * @return string
     */
    private function valueWithMinScale($scale)
    {
        $value = $this->value;

        if ($this->value !== '0' && $scale > $this->scale) {
            $value .= str_repeat('0', $scale - $this->scale);
        }

        return $value;
    }

    /**
     * Adds leading zeros if necessary to the unscaled value to represent the full decimal number.
     *
     * @return string
     */
    private function getUnscaledValueWithLeadingZeros()
    {
        $value = $this->value;
        $targetLength = $this->scale + 1;
        $negative = ($value[0] === '-');
        $length = strlen($value);

        if ($negative) {
            $length--;
        }

        if ($length >= $targetLength) {
            return $this->value;
        }

        if ($negative) {
            $value = substr($value, 1);
        }

        $value = str_pad($value, $targetLength, '0', STR_PAD_LEFT);

        if ($negative) {
            $value = '-' . $value;
        }

        return $value;
    }
}
