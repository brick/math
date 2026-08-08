<?php

declare(strict_types=1);

namespace Brick\Math\Tests;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\PlatformException;
use Closure;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

use function ini_restore;
use function ini_set;
use function sprintf;

/**
 * Tests that a PCRE match failure throws PlatformException instead of being misreported or ignored.
 *
 * A failure is simulated by disabling the JIT and setting the recursion (depth) limit to 1: this makes any match
 * attempt fail as soon as a match is found. The inputs are therefore chosen such that the pattern would match.
 *
 * Each test runs in a separate process: PHP caches compiled patterns per process, and the pcre.jit setting only applies
 * to patterns that are not in the cache yet, so it must be set before the tested pattern is first used.
 */
class PcreFailureTest extends AbstractTestCase
{
    #[RunInSeparateProcess]
    public function testOfDecimalNumber(): void
    {
        self::assertPcreFailureIsDetected(static fn () => BigNumber::of('1.5'));
    }

    #[RunInSeparateProcess]
    public function testOfRationalNumber(): void
    {
        self::assertPcreFailureIsDetected(static fn () => BigNumber::of('1/2'));
    }

    #[RunInSeparateProcess]
    public function testFromBase(): void
    {
        self::assertPcreFailureIsDetected(static fn () => BigInteger::fromBase('zz', 16));
    }

    #[RunInSeparateProcess]
    public function testFromArbitraryBase(): void
    {
        self::assertPcreFailureIsDetected(static fn () => BigInteger::fromArbitraryBase('zz', '01'));
    }

    private static function assertPcreFailureIsDetected(Closure $parse): void
    {
        ini_set('pcre.jit', '0');
        ini_set('pcre.recursion_limit', '1');

        $exception = null;

        try {
            $parse();
        } catch (PlatformException $exception) {
        } finally {
            ini_restore('pcre.jit');
            ini_restore('pcre.recursion_limit');
        }

        self::assertNotNull($exception, sprintf('Expected %s to be thrown.', PlatformException::class));
        self::assertSame('PCRE regular expression matching failed: Recursion limit exhausted. Check the pcre.* ini settings.', $exception->getMessage());
    }
}
