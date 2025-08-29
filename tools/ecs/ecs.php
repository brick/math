<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\OrderedTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__ . '/vendor/brick/coding-standard/ecs.php');

    $libRootPath = __DIR__ . '/../../';

    $ecsConfig->paths(
        [
            $libRootPath . '/src',
            $libRootPath . '/tests',
            __FILE__,
        ],
    );

    $ecsConfig->skip([
        // Allows alignment in test providers
        DuplicateSpacesSniff::class => [$libRootPath . '/tests'],

        // We want to keep BigNumber|int|float|string order
        OrderedTypesFixer::class => null,
        PhpdocTypesOrderFixer::class => null,
    ]);
};
