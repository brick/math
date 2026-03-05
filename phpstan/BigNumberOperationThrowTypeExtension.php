<?php

declare(strict_types=1);

namespace Brick\Math\PHPStan;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\InvalidArgumentException;
use Brick\Math\Exception\NoInverseException;
use Brick\Math\Exception\RoundingNecessaryException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Throwable;

use function in_array;

/**
 * Narrows the throw type of {@see BigNumber} instance methods that accept BigNumber|int|string.
 *
 * When the argument is already the correct {@see BigNumber} subtype, parsing-related exceptions cannot occur.
 * Methods that only throw because of argument parsing have their throw type narrowed to null.
 * Methods with additional inherent throw reasons retain only those specific exceptions.
 */
final class BigNumberOperationThrowTypeExtension implements DynamicMethodThrowTypeExtension
{
    /**
     * Methods that ONLY throw because of argument parsing/conversion.
     * When the argument is already the right type, these cannot throw at all.
     */
    private const NO_THROW_METHODS = [
        BigInteger::class => [
            'plus',
            'minus',
            'multipliedBy',
            'gcd',
            'lcm',
            'and',
            'or',
            'xor',
        ],
        BigDecimal::class => [
            'plus',
            'minus',
            'multipliedBy',
        ],
        BigRational::class => [
            'plus',
            'minus',
            'multipliedBy',
        ],
        BigNumber::class => [
            'isEqualTo',
            'isLessThan',
            'isLessThanOrEqualTo',
            'isGreaterThan',
            'isGreaterThanOrEqualTo',
            'compareTo',
        ],
    ];

    /**
     * Methods that throw because of argument parsing AND have inherent exceptions.
     * When the argument is the right type, only the inherent exceptions remain.
     *
     * class => [method => [residual exception classes]]
     *
     * @var array<class-string, array<string, list<class-string<Throwable>>>>
     */
    private const RESIDUAL_THROW_METHODS = [
        BigInteger::class => [
            'quotient' => [DivisionByZeroException::class],
            'remainder' => [DivisionByZeroException::class],
            'quotientAndRemainder' => [DivisionByZeroException::class],
            'mod' => [DivisionByZeroException::class, InvalidArgumentException::class],
            'modInverse' => [DivisionByZeroException::class, InvalidArgumentException::class, NoInverseException::class],
            'modPow' => [DivisionByZeroException::class, InvalidArgumentException::class],
        ],
        BigDecimal::class => [
            'quotient' => [DivisionByZeroException::class],
            'remainder' => [DivisionByZeroException::class],
            'quotientAndRemainder' => [DivisionByZeroException::class],
            'dividedByExact' => [DivisionByZeroException::class, RoundingNecessaryException::class],
        ],
        BigRational::class => [
            'dividedBy' => [DivisionByZeroException::class],
        ],
        BigNumber::class => [
            'clamp' => [InvalidArgumentException::class],
        ],
    ];

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        // Check class-specific no-throw methods.
        if (isset(self::NO_THROW_METHODS[$className]) && in_array($methodName, self::NO_THROW_METHODS[$className], true)) {
            return true;
        }

        // Check class-specific residual-throw methods.
        if (isset(self::RESIDUAL_THROW_METHODS[$className][$methodName])) {
            return true;
        }

        // BigNumber comparison/clamp methods match any BigNumber subclass.
        if ($methodReflection->getDeclaringClass()->isSubclassOf(BigNumber::class)) {
            if (in_array($methodName, self::NO_THROW_METHODS[BigNumber::class], true)) {
                return true;
            }

            if (isset(self::RESIDUAL_THROW_METHODS[BigNumber::class][$methodName])) {
                return true;
            }
        }

        return false;
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        if ($methodCall->getArgs() === []) {
            return $methodReflection->getThrowType();
        }

        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        // Check residual-throw methods first (O(1) lookup).
        if (isset(self::RESIDUAL_THROW_METHODS[$className][$methodName])) {
            return $this->narrowResidual($methodCall, $scope, $methodReflection);
        }

        // Everything else is a no-throw method.
        return $this->narrowNoThrow($methodCall, $scope, $methodReflection);
    }

    private function narrowNoThrow(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $argType = $scope->getType($methodCall->getArgs()[0]->value);
        $methodName = $methodReflection->getName();

        // For methods declared on BigNumber (compareTo, isEqualTo, etc.),
        // the argument just needs to be any BigNumber or int to avoid parsing exceptions.
        if (in_array($methodName, self::NO_THROW_METHODS[BigNumber::class], true)) {
            $safeType = TypeCombinator::union(new IntegerType(), new ObjectType(BigNumber::class));

            if ($safeType->isSuperTypeOf($argType)->yes()) {
                return null;
            }

            return $methodReflection->getThrowType();
        }

        // For subclass-specific methods (plus, minus, etc.),
        // the argument must be the same type (or convertible without loss).
        $className = $methodReflection->getDeclaringClass()->getName();
        $requiredType = self::getRequiredArgType($className);

        if ($requiredType !== null && $requiredType->isSuperTypeOf($argType)->yes()) {
            return null;
        }

        return $methodReflection->getThrowType();
    }

    private function narrowResidual(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        // For clamp(), check both $min and $max args.
        if ($methodName === 'clamp') {
            return $this->narrowClamp($methodCall, $scope, $methodReflection);
        }

        $argType = $scope->getType($methodCall->getArgs()[0]->value);
        $requiredType = self::getRequiredArgType($className);

        if ($requiredType === null || ! $requiredType->isSuperTypeOf($argType)->yes()) {
            return $methodReflection->getThrowType();
        }

        // modPow has two BigNumber|int|string args.
        if ($methodName === 'modPow' && isset($methodCall->getArgs()[1])) {
            $arg2Type = $scope->getType($methodCall->getArgs()[1]->value);

            if (! $requiredType->isSuperTypeOf($arg2Type)->yes()) {
                return $methodReflection->getThrowType();
            }
        }

        $residual = self::RESIDUAL_THROW_METHODS[$className][$methodName];

        // DivisionByZeroException is impossible when the divisor is guaranteed non-zero.
        if (self::isNonZero($argType)) {
            $residual = array_values(array_filter(
                $residual,
                static fn (string $e): bool => $e !== DivisionByZeroException::class,
            ));
        }

        if ($residual === []) {
            return null;
        }

        return self::buildExceptionUnion($residual);
    }

    private function narrowClamp(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $args = $methodCall->getArgs();

        if (! isset($args[0], $args[1])) {
            return $methodReflection->getThrowType();
        }

        // clamp() is on BigNumber, so we need the caller's type to determine conversion requirements.
        $callerType = $scope->getType($methodCall->var);
        $requiredType = self::getRequiredArgTypeForCallerType($callerType);

        if ($requiredType === null) {
            return $methodReflection->getThrowType();
        }

        $minType = $scope->getType($args[0]->value);
        $maxType = $scope->getType($args[1]->value);

        if ($requiredType->isSuperTypeOf($minType)->yes() && $requiredType->isSuperTypeOf($maxType)->yes()) {
            return new ObjectType(InvalidArgumentException::class);
        }

        return $methodReflection->getThrowType();
    }

    private static function getRequiredArgType(string $className): ?Type
    {
        // int is always safe: it converts losslessly to any BigNumber subtype.
        // BigInteger methods require int|BigInteger.
        // BigDecimal methods accept int|BigInteger|BigDecimal (BigInteger converts losslessly).
        // BigRational methods accept int|BigNumber (all convert losslessly).
        $intType = new IntegerType();

        return match ($className) {
            BigInteger::class => TypeCombinator::union($intType, new ObjectType(BigInteger::class)),
            BigDecimal::class => TypeCombinator::union($intType, new ObjectType(BigInteger::class), new ObjectType(BigDecimal::class)),
            BigRational::class => TypeCombinator::union($intType, new ObjectType(BigNumber::class)),
            default => null,
        };
    }

    private static function getRequiredArgTypeForCallerType(Type $callerType): ?Type
    {
        foreach ([BigInteger::class, BigDecimal::class, BigRational::class] as $class) {
            if ((new ObjectType($class))->isSuperTypeOf($callerType)->yes()) {
                return self::getRequiredArgType($class);
            }
        }

        return null;
    }

    private static function isNonZero(Type $type): bool
    {
        return (new ConstantIntegerType(0))->isSuperTypeOf($type)->no();
    }

    /**
     * @param list<class-string<Throwable>> $exceptionClasses
     */
    private static function buildExceptionUnion(array $exceptionClasses): Type
    {
        $types = [];

        foreach ($exceptionClasses as $exceptionClass) {
            $types[] = new ObjectType($exceptionClass);
        }

        return TypeCombinator::union(...$types);
    }
}
