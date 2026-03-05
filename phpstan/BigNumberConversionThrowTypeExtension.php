<?php

declare(strict_types=1);

namespace Brick\Math\PHPStan;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\IntegerOverflowException;
use Brick\Math\Exception\RoundingNecessaryException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Narrows the throw type of toBigInteger(), toBigDecimal(), toBigRational(), and toInt().
 *
 * When the caller is already the target type, toBigInteger/toBigDecimal/toBigRational are no-ops and cannot throw.
 * When toInt() is called on {@see BigInteger}, only {@see IntegerOverflowException} can be thrown
 * (no {@see RoundingNecessaryException}).
 */
final class BigNumberConversionThrowTypeExtension implements DynamicMethodThrowTypeExtension
{
    private const METHOD_TO_CLASS = [
        'toBigInteger' => BigInteger::class,
        'toBigDecimal' => BigDecimal::class,
        'toBigRational' => BigRational::class,
    ];

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();
        $isBigNumber = $className === BigNumber::class
            || $methodReflection->getDeclaringClass()->isSubclassOf(BigNumber::class);

        if (! $isBigNumber) {
            return false;
        }

        return isset(self::METHOD_TO_CLASS[$methodReflection->getName()])
            || $methodReflection->getName() === 'toInt';
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $callerType = $scope->getType($methodCall->var);
        $methodName = $methodReflection->getName();

        if ($methodName === 'toInt') {
            // BigInteger::toInt() can only throw IntegerOverflowException (no RoundingNecessaryException).
            if ((new ObjectType(BigInteger::class))->isSuperTypeOf($callerType)->yes()) {
                return new ObjectType(IntegerOverflowException::class);
            }

            return $methodReflection->getThrowType();
        }

        $targetClass = self::METHOD_TO_CLASS[$methodName];

        if ((new ObjectType($targetClass))->isSuperTypeOf($callerType)->yes()) {
            return null;
        }

        return $methodReflection->getThrowType();
    }
}
