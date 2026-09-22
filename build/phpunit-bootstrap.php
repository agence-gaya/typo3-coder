<?php

declare(strict_types=1);

use GAYA\Typo3Coder\Configuration\ProjectContext;

$context = ProjectContext::current();
require_once $context->vendorDir . '/autoload.php';

if (getenv('TYPO3_CODER_SUITE') === 'functional') {
    require $context->vendorDir . '/typo3/testing-framework/Resources/Core/Build/FunctionalTestsBootstrap.php';
}
