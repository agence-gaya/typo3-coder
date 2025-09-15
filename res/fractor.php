<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

$fractorConfigBuilder = FractorConfiguration::configure()
    ->withPaths([
        __DIR__ . '/packages'
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);

if (file_exists('fractor.project.php')) {
    include_once 'fractor.project.php';
}

return $fractorConfigBuilder;