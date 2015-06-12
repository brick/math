<?php

namespace Brick\Math;

/**
 * An arbitrarily large rational number.
 *
 * This class is immutable.
 */
class BigRational implements \Serializable
{
    /**
     * The numerator.
     *
     * @var BigInteger
     */
    private $numerator;

    /**
     * The denominator.
     *
     * @var BigInteger
     */
    private $denominator;

    /**
     * Private constructor. Use a factory method to obtain an instance.
     *
     * @param BigInteger $numerator        The numerator.
     * @param BigInteger $denominator      The denominator.
     * @param bool       $checkDemominator Whether to check the denominator for negative and zero.
     *
     * @throws ArithmeticException If the denominator is zero.
     */
    public function __construct(BigInteger $numerator, BigInteger $denominator, $checkDemominator)
    {
        if ($checkDemominator) {
            if ($denominator->isZero()) {
                throw new ArithmeticException('The denominator must not be zero.');
            }

            if ($denominator->isNegative()) {
                $numerator   = $numerator->negated();
                $denominator = $denominator->negated();
            }
        }

        $this->numerator   = $numerator;
        $this->denominator = $denominator;
    }

    /**
     * Creates a BigRational out of a numerator and a denominator.
     *
     * If the denominator is negative, the signs of both the numerator and the denominator
     * will be inverted to ensure that the denominator is always positive.
     *
     * @param BigInteger|int|string $numerator   The numerator.
     * @param BigInteger|int|string $denominator The denominator.
     *
     * @return BigRational
     *
     * @throws ArithmeticException If the denominator is zero.
     */
    public static function of($numerator, $denominator)
    {
        $numerator   = BigInteger::of($numerator);
        $denominator = BigInteger::of($denominator);

        return new BigRational($numerator, $denominator, true);
    }

    /**
     * Parses the string output of a BigRational.
     *
     * @param string $number
     *
     * @return BigRational
     *
     * @throws \InvalidArgumentException If the string cannot be parsed.
     */
    public static function parse($number)
    {
        if (preg_match('/^(\-?[0-9]+)(?:\/([0-9]+))?$/', $number, $matches) !== 1) {
            throw new \InvalidArgumentException('Invalid BigRational string representation.');
        }

        return isset($matches[2])
            ? BigRational::of($matches[1], $matches[2])
            : BigRational::of($matches[1], 1);
    }

    /**
     * Internal factory method transparently used by methods accepting mixed numbers.
     *
     * @param BigRational|BigInteger|int|string $number
     *
     * @return BigRational
     */
    private static function get($number)
    {
        if ($number instanceof BigRational) {
            return $number;
        }

        if ($number instanceof BigInteger) {
            return new BigRational($number, BigInteger::of(1), false);
        }

        if (is_int($number)) {
            return new BigRational(BigInteger::of($number), BigInteger::of(1), false);
        }

        return BigRational::parse($number);
    }

    /**
     * @return BigInteger
     */
    public function getNumerator()
    {
        return $this->numerator;
    }

    /**
     * @return BigInteger
     */
    public function getDenominator()
    {
        return $this->denominator;
    }

    /**
     * @param BigRational|BigInteger|int|string $that
     *
     * @return BigRational
     */
    public function plus($that)
    {
        $that = BigRational::get($that);

        $numerator   = $this->numerator->multipliedBy($that->denominator);
        $numerator   = $numerator->plus($that->numerator->multipliedBy($this->denominator));
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new BigRational($numerator, $denominator, false);
    }

    /**
     * @param BigRational|BigInteger|int|string $that
     *
     * @return BigRational
     */
    public function minus($that)
    {
        $that = BigRational::get($that);

        $numerator   = $this->numerator->multipliedBy($that->denominator);
        $numerator   = $numerator->minus($that->numerator->multipliedBy($this->denominator));
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new BigRational($numerator, $denominator, false);
    }

    /**
     * @param BigRational|BigInteger|int|string $that
     *
     * @return BigRational
     */
    public function multipliedBy($that)
    {
        $that = BigRational::get($that);

        $numerator   = $this->numerator->multipliedBy($that->numerator);
        $denominator = $this->denominator->multipliedBy($that->denominator);

        return new BigRational($numerator, $denominator, false);
    }

    /**
     * @param BigRational|BigInteger|int|string $that
     *
     * @return BigRational
     */
    public function dividedBy($that)
    {
        $that = BigRational::get($that);

        $numerator   = $this->numerator->multipliedBy($that->denominator);
        $denominator = $this->denominator->multipliedBy($that->numerator);

        return new BigRational($numerator, $denominator, true);
    }

    /**
     * Returns the reciprocal of this BigRational.
     *
     * The reciprocal has the numerator and denominator swapped.
     *
     * @return BigRational
     *
     * @throws ArithmeticException If the numerator is zero.
     */
    public function reciprocal()
    {
        return new BigRational($this->denominator, $this->numerator, true);
    }

    /**
     * Returns the absolute value of this BigRational.
     *
     * @return BigRational
     */
    public function abs()
    {
        return new BigRational($this->numerator->abs(), $this->denominator, false);
    }

    /**
     * Returns the negated value of this BigRational.
     *
     * @return BigRational
     */
    public function negated()
    {
        return new BigRational($this->numerator->negated(), $this->denominator, false);
    }

    /**
     * Returns the simplified value of this BigRational.
     *
     * @return BigRational
     */
    public function simplified()
    {
        $gcd = $this->numerator->gcd($this->denominator);

        $numerator = $this->numerator->dividedBy($gcd);
        $denominator = $this->denominator->dividedBy($gcd);

        return new BigRational($numerator, $denominator, false);
    }

    /**
     * Compares this number to the given one.
     *
     * @param BigRational|BigInteger|int|string $that
     *
     * @return int [-1,0,1]
     */
    public function compareTo($that)
    {
        return $this->minus($that)->getSign();
    }

    /**
     * Checks if this number is equal to the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return bool
     */
    public function isEqualTo($that)
    {
        return $this->compareTo($that) === 0;
    }

    /**
     * Checks if this number is strictly lower than the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return bool
     */
    public function isLessThan($that)
    {
        return $this->compareTo($that) < 0;
    }

    /**
     * Checks if this number is lower than or equal to the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return bool
     */
    public function isLessThanOrEqualTo($that)
    {
        return $this->compareTo($that) <= 0;
    }

    /**
     * Checks if this number is strictly greater than the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return bool
     */
    public function isGreaterThan($that)
    {
        return $this->compareTo($that) > 0;
    }

    /**
     * Checks if this number is greater than or equal to the given one.
     *
     * @param BigInteger|int|string $that
     *
     * @return bool
     */
    public function isGreaterThanOrEqualTo($that)
    {
        return $this->compareTo($that) >= 0;
    }

    /**
     * Returns the sign of this number.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    public function getSign()
    {
        return $this->numerator->getSign();
    }

    /**
     * Checks if this number equals zero.
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->numerator->isZero();
    }

    /**
     * Checks if this number is strictly negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->numerator->isNegative();
    }

    /**
     * Checks if this number is negative or zero.
     *
     * @return bool
     */
    public function isNegativeOrZero()
    {
        return $this->numerator->isNegativeOrZero();
    }

    /**
     * Checks if this number is strictly positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->numerator->isPositive();
    }

    /**
     * Checks if this number is positive or zero.
     *
     * @return bool
     */
    public function isPositiveOrZero()
    {
        return $this->numerator->isPositiveOrZero();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $numerator   = (string) $this->numerator;
        $denominator = (string) $this->denominator;

        if ($denominator === '1') {
            return $numerator;
        }

        return $this->numerator . '/' . $this->denominator;
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
        return $this->numerator . '/' . $this->denominator;
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
        if ($this->numerator !== null || $this->denominator !== null) {
            throw new \LogicException('unserialize() is an internal function, it must not be called directly.');
        }

        list ($numerator, $denominator) = explode('/', $value);

        $this->numerator   = BigInteger::of($numerator);
        $this->denominator = BigInteger::of($denominator);
    }
}
