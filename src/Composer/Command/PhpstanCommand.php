<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PhpstanCommand extends AbstractPhpstanCommand
{
    public function __construct()
    {
        parent::__construct('phpstan');
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        $baseline = $context->absolute('build/phpstan.baseline.neon');
        $arguments = ['analyze', '--configuration', $this->temporaryPhpstanConfig($context, $baseline)];
        return $this->runTool($context, $input, $output, 'phpstan', $arguments);
    }
}
