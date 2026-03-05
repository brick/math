<?php

declare(strict_types=1);

namespace Brick\Math\PHPStan;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\InvalidArgumentException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use Throwable;

/**
 * Narrows the throw type of methods with a {@see RoundingMode} parameter.
 *
 * When the rounding mode is known to not be {@see RoundingMode::Unnecessary},
 * {@see RoundingNecessaryException} cannot occur.
 * Each method retains only its inherent exceptions
 * (e.g. {@see DivisionByZeroException}, {@see NegativeNumberException}).
 *
 * For toScale(), {@see InvalidArgumentException} is also removed when the scale is non-negative.
 */
final class RoundingModeThrowTypeExtension implements DynamicMethodThrowTypeExtension
{
    /**
     * class => method => [rounding mode arg index, residual exception classes].
     *
     * @var array<class-string, array<string, array{int, list<class-string<Throwable>>}>>
     */
    private const METHODS = [
        BigInteger::class => [
            'dividedBy' => [1, [MathException::class, DivisionByZeroException::class]],
            'sqrt' => [0, [NegativeNumberException::class]],
        ],
        BigDecimal::class => [
            'dividedBy' => [2, [MathException::class, DivisionByZeroException::class]],
            'sqrt' => [1, [NegativeNumberException::class]],
        ],
    ];

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        if ($methodName === 'toScale') {
            return $className === BigNumber::class
                || $className === BigInteger::class
                || $className === BigDecimal::class
                || $className === BigRational::class;
        }

        return isset(self::METHODS[$className][$methodName]);
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $methodName = $methodReflection->getName();

        if ($methodName === 'toScale') {
            return $this->narrowToScale($methodCall, $scope, $methodReflection);
        }

        $className = $methodReflection->getDeclaringClass()->getName();
        $config = self::METHODS[$className][$methodName] ?? null;

        if ($config === null) {
            return $methodReflection->getThrowType();
        }

        [$roundingModeArgIndex, $residualExceptions] = $config;

        return $this->narrowByRoundingMode($methodCall, $scope, $methodReflection, $roundingModeArgIndex, $residualExceptions);
    }

    /**
     * toScale() has two independent narrowing axes:
     * - scale is non-negative → removes {@see InvalidArgumentException}
     * - rounding mode is not Unnecessary → removes {@see RoundingNecessaryException}
     */
    private function narrowToScale(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $args = $methodCall->getArgs();

        $scaleIsNonNegative = isset($args[0])
            && IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($scope->getType($args[0]->value))->yes();

        $roundingIsNotUnnecessary = isset($args[1])
            && self::isNotUnnecessary($scope->getType($args[1]->value));

        if ($scaleIsNonNegative && $roundingIsNotUnnecessary) {
            return null;
        }

        $residual = [];

        if (! $scaleIsNonNegative) {
            $residual[] = new ObjectType(InvalidArgumentException::class);
        }

        if (! $roundingIsNotUnnecessary) {
            $residual[] = new ObjectType(RoundingNecessaryException::class);
        }

        return TypeCombinator::union(...$residual);
    }

    /**
     * @param list<class-string<Throwable>> $residualExceptions
     */
    private function narrowByRoundingMode(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
        int $roundingModeArgIndex,
        array $residualExceptions,
    ): ?Type {
        $args = $methodCall->getArgs();

        $roundingIsNotUnnecessary = isset($args[$roundingModeArgIndex])
            && self::isNotUnnecessary($scope->getType($args[$roundingModeArgIndex]->value));

        // For dividedBy(), MathException is only thrown when parsing the divisor argument.
        // When the divisor is already the right type (or int), MathException is impossible.
        $argType = isset($args[0]) ? $scope->getType($args[0]->value) : null;
        $argIsSafe = $argType !== null && $this->isDivisorArgSafe($methodReflection, $argType);

        // DivisionByZeroException is impossible when the divisor is a non-zero int.
        $divisorIsNonZero = $argType !== null && self::isNonZero($argType);

        if (! $roundingIsNotUnnecessary && ! $argIsSafe && ! $divisorIsNonZero) {
            return $methodReflection->getThrowType();
        }

        $types = [];

        foreach ($residualExceptions as $exceptionClass) {
            if ($argIsSafe && $exceptionClass === MathException::class) {
                continue;
            }

            if ($roundingIsNotUnnecessary && $exceptionClass === RoundingNecessaryException::class) {
                continue;
            }

            if ($divisorIsNonZero && $exceptionClass === DivisionByZeroException::class) {
                continue;
            }

            $types[] = new ObjectType($exceptionClass);
        }

        if ($types === []) {
            return null;
        }

        return TypeCombinator::union(...$types);
    }

    /**
     * Checks if the divisor argument is already the correct type (no parsing needed).
     */
    private function isDivisorArgSafe(MethodReflection $methodReflection, Type $argType): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();

        $safeType = match ($className) {
            BigInteger::class => TypeCombinator::union(new IntegerType(), new ObjectType(BigInteger::class)),
            BigDecimal::class => TypeCombinator::union(
                new IntegerType(),
                new ObjectType(BigInteger::class),
                new ObjectType(BigDecimal::class),
            ),
            default => null,
        };

        return $safeType !== null && $safeType->isSuperTypeOf($argType)->yes();
    }

    /**
     * Checks if the type is guaranteed to be non-zero (excludes {@see DivisionByZeroException}).
     *
     * Non-zero int ranges and {@see BigNumber} instances (whose zeroness can't be statically proven) are considered non-zero.
     * Only pure int types that include zero are treated as potentially zero.
     */
    private static function isNonZero(Type $type): bool
    {
        $zeroType = new ConstantIntegerType(0);

        return $zeroType->isSuperTypeOf($type)->no();
    }

    private static function isNotUnnecessary(Type $roundingModeType): bool
    {
        $unnecessaryType = new EnumCaseObjectType(RoundingMode::class, 'Unnecessary');

        return $unnecessaryType->isSuperTypeOf($roundingModeType)->no();
    }
}
