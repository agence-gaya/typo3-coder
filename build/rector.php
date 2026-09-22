<?php

declare(strict_types=1);

use GAYA\Typo3Coder\Configuration\Overrides;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO313\v4\MigratePluginContentElementAndPluginSubtypesRector;

$context = ProjectContext::current();
$phpVersion = $context->phpVersion();
$phpSet = LevelSetList::class . '::UP_TO_PHP_' . intdiv($phpVersion, 10000) . intdiv($phpVersion % 10000, 100);
if (!defined($phpSet)) {
    throw new RuntimeException('The installed Rector does not support PHP target ' . $phpVersion . '. Update Rector to support the running PHP version.');
}

$rectorConfigBuilder = RectorConfig::configure()
    ->withPaths($context->phpFiles())
    ->withPhpVersion($phpVersion)
    ->withSets([constant($phpSet)])
    ->withSkip([
        $context->rootDir . '/**/node_modules/*',
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
        $context->rootDir . '/**/Configuration/ExtensionBuilder/*',
        // We skip those directories on purpose as there might be node_modules or similar
        // that include typescript which would result in false positive processing
        $context->rootDir . '/**/Resources/**/node_modules/*',
        $context->rootDir . '/**/Resources/**/NodeModules/*',
        $context->rootDir . '/**/Resources/**/BowerComponents/*',
        $context->rootDir . '/**/Resources/**/bower_components/*',
        $context->rootDir . '/**/Resources/**/build/*',
        $context->rootDir . '/vendor/*',
        $context->rootDir . '/Build/*',
        $context->rootDir . '/public/*',
        $context->rootDir . '/.github/*',
        $context->rootDir . '/.Build/*',

        ...$context->exclusions(),

        // Disable creation of CTypeMigration.php migration file
        MigratePluginContentElementAndPluginSubtypesRector::class,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);

return Overrides::apply('rector', $rectorConfigBuilder, $context);
