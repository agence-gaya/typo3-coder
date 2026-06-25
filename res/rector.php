<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO313\v4\MigratePluginContentElementAndPluginSubtypesRector;

$rectorConfigBuilder = RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages',
    ])
    ->withSkip([
        'node_modules/*',
    ])
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3LevelSetList::UP_TO_TYPO3_14,
        SetList::PRIVATIZATION,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ])
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])

    // this will not import root namespace classes, like \DateTime or \Exception
    ->withImportNames(true, true, false, true)
    ->withSkip([
        // @see https://github.com/sabbelasichon/typo3-rector/issues/2536
        __DIR__ . '/**/Configuration/ExtensionBuilder/*',
        // We skip those directories on purpose as there might be node_modules or similar
        // that include typescript which would result in false positive processing
        __DIR__ . '/**/Resources/**/node_modules/*',
        __DIR__ . '/**/Resources/**/NodeModules/*',
        __DIR__ . '/**/Resources/**/BowerComponents/*',
        __DIR__ . '/**/Resources/**/bower_components/*',
        __DIR__ . '/**/Resources/**/build/*',
        __DIR__ . '/vendor/*',
        __DIR__ . '/Build/*',
        __DIR__ . '/public/*',
        __DIR__ . '/.github/*',
        __DIR__ . '/.Build/*',

        // Disable creation of CTypeMigration.php migration file
        MigratePluginContentElementAndPluginSubtypesRector::class,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);

if (file_exists('rector.project.php')) {
    include_once __DIR__ . '/rector.project.php';
} else {
    $rectorConfigBuilder
        ->withPhpVersion(PhpVersion::PHP_85)
        ->withSets([
            LevelSetList::UP_TO_PHP_85,
        ]);
}

return $rectorConfigBuilder;
