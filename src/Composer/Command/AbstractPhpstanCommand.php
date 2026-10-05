<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;

abstract class AbstractPhpstanCommand extends AbstractCommand
{
    private function ensureBuildDirectory(ProjectContext $context): void
    {
        if (!is_dir($context->absolute('build'))) {
            mkdir($context->absolute('build'), 0775, true);
        }
    }

    protected function temporaryPhpstanConfig(ProjectContext $context, string $baseline): string
    {
        $this->ensureBuildDirectory($context);
        $includes = [dirname(__DIR__, 3) . '/build/phpstan.neon'];
        if (is_file($baseline)) {
            $includes[] = $baseline;
        }
        if (is_file($context->absolute('build/phpstan.neon'))) {
            $includes[] = $context->absolute('build/phpstan.neon');
        }
        $file = tempnam(sys_get_temp_dir(), 'coder-phpstan-') . '.neon';
        file_put_contents($file, "includes:\n    - " . implode("\n    - ", $includes) . "\nparameters:\n    level: " . $context->phpstanLevel() . "\n    paths:\n        - " . implode("\n        - ", $context->phpFiles()) . "\n");
        return $file;
    }
}
