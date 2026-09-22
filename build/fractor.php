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
        '*/node_modules/*',
        '*/vendor/*',
        '*/.build/*',
        '*/.Build/*',
        '*/Build/*',
        '*/build/*',
        '*/var/*',
        ...$context->exclusions(),
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);

return Overrides::apply('fractor', $fractorConfigBuilder, $context);
