<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

use GAYA\Typo3Coder\Configuration\Overrides;
use GAYA\Typo3Coder\Configuration\ProjectContext;

$context = ProjectContext::current();

$fractorConfigBuilder = FractorConfiguration::configure()
    ->withPaths($context->paths())
    ->withSkip([
        $context->rootDir . '/node_modules/*',
        $context->rootDir . '/vendor/*',
        $context->rootDir . '/.build/*',
        $context->rootDir . '/.Build/*',
        $context->rootDir . '/Build/*',
        $context->rootDir . '/build/*',
        $context->rootDir . '/var/*',

        $context->rootDir . '/*/node_modules/*',
        $context->rootDir . '/*/vendor/*',
        $context->rootDir . '/*/.build/*',
        $context->rootDir . '/*.Build/*',
        $context->rootDir . '/*/Build/*',
        $context->rootDir . '/*/build/*',
        $context->rootDir . '/*/var/*',

        ...$context->exclusions(),
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);

return Overrides::apply('fractor', $fractorConfigBuilder, $context);

