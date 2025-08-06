<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

return FractorConfiguration::configure()
    ->withPaths([
        __DIR__ . '/packages'
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_10,
        Typo3LevelSetList::UP_TO_TYPO3_11,
        Typo3LevelSetList::UP_TO_TYPO3_12,
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);
