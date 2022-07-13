<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $config): void {
    $config->import(SetList::DEAD_CODE);
    $config->import(LevelSetList::UP_TO_PHP_74);
    $config->import(PHPUnitSetList::PHPUNIT_EXCEPTION);
    $config->import(PHPUnitSetList::PHPUNIT_SPECIFIC_METHOD);
    $config->import(PHPUnitLevelSetList::UP_TO_PHPUNIT_100);
    $config->import(PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER);
    $config->services()->set(TypedPropertyRector::class);

    $config->paths([__DIR__ . '/src', __DIR__ . '/tests']);
    $config->phpVersion(PhpVersion::PHP_74);
    $config->parallel();
    $config->importNames();
    $config->importNames();
    $config->importShortClasses();
};
