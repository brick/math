<?php

use Brick\Math\Internal\Calculator;

require 'vendor/autoload.php';

/**
 * @return Calculator
 */
function getCalculatorImplementation()
{
    switch ($calculator = getenv('CALCULATOR')) {
        case 'GMP':
            $calculator = new Calculator\GmpCalculator();
            break;

        case 'BCMath':
            $calculator = new Calculator\BcMathCalculator();
            break;

        case 'Native':
            $calculator = new Calculator\NativeCalculator();
            break;

        default:
            if ($calculator === false) {
                echo 'CALCULATOR environment variable not set!' . PHP_EOL;
            } else {
                echo 'Unknown calculator: ' . $calculator . PHP_EOL;
            }

            echo 'Example usage: CALCULATOR={calculator} vendor/bin/phpunit' . PHP_EOL;
            echo 'Available calculators: GMP, BCMath, Native' . PHP_EOL;
            exit(1);
    }

    echo 'Using ', get_class($calculator), PHP_EOL;

    return $calculator;
}

Calculator::set(getCalculatorImplementation());
