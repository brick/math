<?php

declare(strict_types=1);

namespace Brick\Math\PHPStan;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\InvalidArgumentException;
use Brick\Math\Exception\RandomSourceException;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

use function count;
use function in_array;

/**
 * Narrows the throw type of {@see BigNumber} static factory methods.
 *
 * When arguments are already instances of the expected {@see BigNumber} subtype,
 * parsing and conversion exceptions cannot occur.
 */
final class BigNumberOfThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    private const SUPPORTED_METHODS = [
        BigNumber::class => ['of', 'ofNullable', 'min', 'max', 'sum'],
        BigInteger::class => ['of', 'ofNullable', 'gcdAll', 'lcmAll', 'randomRange'],
        BigDecimal::class => ['of', 'ofNullable', 'ofUnscaledValue'],
        BigRational::class => ['of', 'ofNullable', 'ofFraction'],
    ];

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        return isset(self::SUPPORTED_METHODS[$className])
            && in_array($methodName, self::SUPPORTED_METHODS[$className], true);
    }

    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        if (count($methodCall->getArgs()) < 1) {
            return $methodReflection->getThrowType();
        }

        $calledOnClass = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        // Methods with residual exceptions after parsing is eliminated.
        if ($methodName === 'ofFraction') {
            return $this->narrowOfFraction($methodCall, $scope, $methodReflection);
        }

        if ($methodName === 'randomRange') {
            return $this->narrowRandomRange($methodCall, $scope, $methodReflection);
        }

        $noThrowType = self::getNoThrowType($calledOnClass);

        if ($noThrowType === null) {
            return $methodReflection->getThrowType();
        }

        // Check ALL arguments (including variadic) match the required type.
        $allMatch = true;

        foreach ($methodCall->getArgs() as $arg) {
            $argType = $scope->getType($arg->value);

            if (! $noThrowType->isSuperTypeOf($argType)->yes()) {
                $allMatch = false;

                break;
            }
        }

        if ($allMatch) {
            return null;
        }

        // int and numeric-string arguments cannot contain "/", so DivisionByZeroException is impossible.
        // This applies to of() and ofNullable() on all classes.
        if (in_array($methodName, ['of', 'ofNullable'], true)) {
            return $this->narrowNonRationalInput($methodCall, $scope, $methodReflection);
        }

        return $methodReflection->getThrowType();
    }

    private function narrowOfFraction(
        StaticCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        if (count($methodCall->getArgs()) < 2) {
            return $methodReflection->getThrowType();
        }

        // int|BigInteger args cannot cause parsing exceptions in BigInteger::of().
        $safeType = TypeCombinator::union(new IntegerType(), new ObjectType(BigInteger::class));

        foreach ($methodCall->getArgs() as $arg) {
            $argType = $scope->getType($arg->value);

            if (! $safeType->isSuperTypeOf($argType)->yes()) {
                return $methodReflection->getThrowType();
            }
        }

        // DivisionByZeroException is impossible when the denominator is guaranteed non-zero.
        $denominatorType = $scope->getType($methodCall->getArgs()[1]->value);

        if (self::isNonZero($denominatorType)) {
            return null;
        }

        return new ObjectType(DivisionByZeroException::class);
    }

    private function narrowRandomRange(
        StaticCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        if (count($methodCall->getArgs()) < 2) {
            return $methodReflection->getThrowType();
        }

        $bigIntegerType = new ObjectType(BigInteger::class);

        // Only check first two args ($min, $max) — third is the callable.
        for ($i = 0; $i < 2; $i++) {
            $argType = $scope->getType($methodCall->getArgs()[$i]->value);

            if (! $bigIntegerType->isSuperTypeOf($argType)->yes()) {
                return $methodReflection->getThrowType();
            }
        }

        // Both args are BigInteger — only InvalidArgumentException + RandomSourceException remain.
        return TypeCombinator::union(
            new ObjectType(InvalidArgumentException::class),
            new ObjectType(RandomSourceException::class),
        );
    }

    /**
     * When all arguments are int or numeric-string, the input cannot contain "/",
     * so {@see DivisionByZeroException} is impossible.
     */
    private function narrowNonRationalInput(
        StaticCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $nonRationalType = TypeCombinator::union(
            new IntegerType(),
            new AccessoryNumericStringType(),
            new ObjectType(BigNumber::class),
        );

        foreach ($methodCall->getArgs() as $arg) {
            $argType = $scope->getType($arg->value);

            if (! $nonRationalType->isSuperTypeOf($argType)->yes()) {
                return $methodReflection->getThrowType();
            }
        }

        $defaultThrowType = $methodReflection->getThrowType();

        if ($defaultThrowType === null) {
            return null;
        }

        return TypeCombinator::remove($defaultThrowType, new ObjectType(DivisionByZeroException::class));
    }

    /**
     * Returns the argument type for which the method cannot throw.
     *
     * int arguments are always safe: _of(int) produces a {@see BigInteger} which converts losslessly to all subtypes.
     *
     * - {@see BigNumber}::of/min/max/sum(int|{@see BigNumber}) — no parsing, returned as-is.
     * - {@see BigInteger}::of/gcdAll/lcmAll(int|{@see BigInteger}) — no parsing, no conversion.
     * - {@see BigDecimal}::of/ofUnscaledValue(int|{@see BigInteger}|{@see BigDecimal}) — BigInteger converts losslessly to BigDecimal.
     * - {@see BigRational}::of(int|{@see BigNumber}) — all BigNumber types convert losslessly to BigRational.
     */
    private static function isNonZero(Type $type): bool
    {
        return (new ConstantIntegerType(0))->isSuperTypeOf($type)->no();
    }

    private static function getNoThrowType(string $calledOnClass): ?Type
    {
        $intType = new IntegerType();

        return match ($calledOnClass) {
            BigNumber::class => TypeCombinator::union($intType, new ObjectType(BigNumber::class)),
            BigInteger::class => TypeCombinator::union($intType, new ObjectType(BigInteger::class)),
            BigDecimal::class => TypeCombinator::union($intType, new ObjectType(BigInteger::class), new ObjectType(BigDecimal::class)),
            BigRational::class => TypeCombinator::union($intType, new ObjectType(BigNumber::class)),
            default => null,
        };
    }
}
