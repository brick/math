<?php

/**
 * This script stress tests calculators with random large numbers and ensures that all implementations return the same
 * results. It is designed to run in an infinite loop unless a bug is found.
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Brick\Math\Internal\Calculator;

if ($argc !== 2) {
    echo 'Usage: php random-tests.php <max number of digits>', PHP_EOL;
    exit(1);
}

$maxDigits = (int) $argv[1];

if ($maxDigits < 1) {
    echo 'Max digits must be > 1', PHP_EOL;
    exit(1);
}

(new class($maxDigits) {
    private readonly Calculator\GmpCalculator $gmp;

    private readonly Calculator\BcMathCalculator $bcmath;

    private readonly Calculator\NativeCalculator $native;

    private int $testCounter = 0;

    private float $lastOutputTime = 0.0;

    private int $currentSecond = 0;

    private int $currentSecondTestCounter = 0;

    private int $testsPerSecond = 0;

    public function __construct(
        private readonly int $maxDigits,
    ) {
        $this->gmp = new Calculator\GmpCalculator();
        $this->bcmath = new Calculator\BcMathCalculator();
        $this->native = new Calculator\NativeCalculator();
    }

    public function __invoke(): void
    {
        for (; ;) {
            $a = $this->generateRandomNumber();
            $b = $this->generateRandomNumber();
            $c = $this->generateRandomNumber();

            $this->runTests($a, $b);
            $this->runTests($b, $a);

            if ($a !== '0') {
                $this->runTests("-$a", $b);
                $this->runTests($b, "-$a");
            }

            if ($b !== '0') {
                $this->runTests($a, "-$b");
                $this->runTests("-$b", $a);
            }

            if ($a !== '0' && $b !== '0') {
                $this->runTests("-$a", "-$b");
                $this->runTests("-$b", "-$a");
            }

            if ($c !== '0') {
                $this->test("$a POW $b MOD $c", fn (Calculator $calc) => $calc->modPow($a, $b, $c));
                $this->test("-$a POW $b MOD $c", fn (Calculator $calc) => $calc->modPow("-$a", $b, $c));
            }

            foreach ([$a, $b] as $n) {
                $this->test("SQRT $n", fn (Calculator $c) => $c->sqrt($n));

                for ($exp = 0; $exp <= 3; $exp++) {
                    $this->test("$n POW $exp", fn (Calculator $calc) => $calc->pow($n, $exp));

                    if ($n !== '0') {
                        $this->test("-$n POW $exp", fn (Calculator $calc) => $calc->pow("-$n", $exp));
                    }
                }
            }
        }
    }

    /**
     * @param string $a The left operand.
     * @param string $b The right operand.
     */
    private function runTests(string $a, string $b): void
    {
        $this->test("$a + $b", fn (Calculator $c) => $c->add($a, $b));
        $this->test("$a - $b", fn (Calculator $c) => $c->sub($a, $b));
        $this->test("$a * $b", fn (Calculator $c) => $c->mul($a, $b));

        if ($b !== '0') {
            $this->test("$a / $b", fn (Calculator $c) => $c->divQR($a, $b));
            $this->test("$a MOD $b", fn (Calculator $c) => $c->mod($a, $b));
        }

        if ($b !== '0' && $b[0] !== '-') {
            $this->test("INV $a MOD $b", fn (Calculator $c) => $c->modInverse($a, $b));
        }

        $this->test("GCD $a, $b", fn (Calculator $c) => $c->gcd($a, $b));
        $this->test("LCM $a, $b", fn (Calculator $c) => $c->lcm($a, $b));

        $this->test("$a AND $b", fn (Calculator $c) => $c->and($a, $b));
        $this->test("$a OR $b", fn (Calculator $c) => $c->or($a, $b));
        $this->test("$a XOR $b", fn (Calculator $c) => $c->xor($a, $b));
    }

    /**
     * @param string                     $test     A string representing the test being executed.
     * @param Closure(Calculator): mixed $callback A callback function accepting a Calculator instance and returning a calculation result.
     */
    private function test(string $test, Closure $callback): void
    {
        $gmpResult = $callback($this->gmp);
        $bcmathResult = $callback($this->bcmath);
        $nativeResult = $callback($this->native);

        if ($gmpResult !== $bcmathResult) {
            $this->failure('GMP', 'BCMath', $test, $gmpResult, $bcmathResult);
        }

        if ($gmpResult !== $nativeResult) {
            $this->failure('GMP', 'Native', $test, $gmpResult, $nativeResult);
        }

        $this->testCounter++;
        $this->currentSecondTestCounter++;

        $time = microtime(true);
        $second = (int) $time;

        if ($second !== $this->currentSecond) {
            $this->currentSecond = $second;
            $this->testsPerSecond = $this->currentSecondTestCounter;
            $this->currentSecondTestCounter = 0;
        }

        if ($time - $this->lastOutputTime >= 0.1) {
            echo "\r", number_format($this->testCounter), ' (', number_format($this->testsPerSecond) . ' / s)';
            $this->lastOutputTime = $time;
        }
    }

    /**
     * @param string $c1   The name of the first calculator.
     * @param string $c2   The name of the second calculator.
     * @param string $test A string representing the test being executed.
     * @param string $v1   The value returned by the first calculator.
     * @param string $v2   The value returned by the second calculator.
     */
    private function failure(string $c1, string $c2, string $test, string $v1, string $v2): never
    {
        echo PHP_EOL;
        echo 'FAILURE!', PHP_EOL;
        echo $c1, ' vs ', $c2, PHP_EOL;
        echo "$v1 != $v2", PHP_EOL;
        echo $test, PHP_EOL;
        exit(1);
    }

    private function generateRandomNumber(): string
    {
        $length = random_int(1, $this->maxDigits);

        $number = '';

        for ($i = 0; $i < $length; $i++) {
            $number .= random_int(0, 9);
        }

        $number = ltrim($number, '0');

        if ($number === '') {
            return '0';
        }

        return $number;
    }
})();
