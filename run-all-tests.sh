#!/bin/bash

# Runs all tests.
# Accepts as an optional argument a directory to store the coverage reports.

set -e

DIR=$1

coverage() {
  if [[ ! -z $DIR ]]; then
    echo "--coverage-php $DIR/$1"
  fi
}

CALCULATOR=GMP    vendor/bin/phpunit --configuration phpunit-main.xml   $(coverage main-gmp.cov)
CALCULATOR=BCMath vendor/bin/phpunit --configuration phpunit-main.xml   $(coverage main-bcmath.cov)
CALCULATOR=Native vendor/bin/phpunit --configuration phpunit-main.xml   $(coverage main-native.cov)
                  vendor/bin/phpunit --configuration phpunit-detect.xml $(coverage detect.cov)
