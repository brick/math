<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\OrderedTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$libRootPath = realpath(__DIR__ . '/../..');

return ECSConfig::configure()
    ->withSets([__DIR__ . '/vendor/brick/coding-standard/ecs.php'])
    ->withPaths(
        [
            $libRootPath . '/src',
            $libRootPath . '/tests',
            $libRootPath . '/phpunit.php',
            $libRootPath . '/random-tests.php',
            __FILE__,
        ],
    )
    ->withSkip([
        // Allows alignment in test providers
        DuplicateSpacesSniff::class => [$libRootPath . '/tests'],

        // We want to keep BigNumber|int|string order
        OrderedTypesFixer::class => null,
        PhpdocTypesOrderFixer::class => null,
    ]);
