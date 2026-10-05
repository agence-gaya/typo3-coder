<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

final class AllCommand extends CommandGroup
{
    public function __construct()
    {
        parent::__construct('coder:all', 'Run all code tools and test suites', [
            'coder:rector',
            'coder:fractor',
            'coder:php-cs-fixer',
            'coder:phplint',
            'coder:typoscript-lint',
            'coder:yaml-lint',
            'coder:phpstan',
            'coder:tests:unit',
            'coder:tests:functional',
        ]);
    }
}
