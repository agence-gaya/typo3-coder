<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Rector\Set\ValueObject\SetList;

$rectorConfigBuilder = RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::TYPO3_10,
        Typo3SetList::TYPO3_11,
        Typo3SetList::TYPO3_12,
        Typo3SetList::TYPO3_13,
        SetList::PRIVATIZATION,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ])
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH
    ])
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.3.0-8.3.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '13.4.0-13.4.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => []
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
        \Ssch\TYPO3Rector\TYPO313\v4\MigratePluginContentElementAndPluginSubtypesRector::class
    ]);

if (file_exists('rector.project.php')) {
    include_once 'rector.project.php';
}

return $rectorConfigBuilder;