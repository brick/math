<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $config): void {
    $config->import(SetList::PSR_12);
    $config->import(SetList::CLEAN_CODE);
    $config->import(SetList::DOCTRINE_ANNOTATIONS);
    $config->import(SetList::SPACES);
    $config->import(SetList::PHPUNIT);
    $config->import(SetList::SYMPLIFY);
    $config->import(SetList::ARRAY);
    $config->import(SetList::COMMON);
    $config->import(SetList::COMMENTS);
    $config->import(SetList::CONTROL_STRUCTURES);
    $config->import(SetList::DOCBLOCK);
    $config->import(SetList::NAMESPACES);
    $config->import(SetList::STRICT);

    $config->services()
        ->remove(PhpUnitTestClassRequiresCoversFixer::class)
    ;

    $config->parallel();
    $config->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);
};
