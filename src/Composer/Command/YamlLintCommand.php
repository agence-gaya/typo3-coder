<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class YamlLintCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('yaml-lint');
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        $arguments = $context->yamlFiles();
        if ($arguments === []) {
            $output->writeln('No YAML files found; nothing to run.');
            return 0;
        }
        if ($this->isContinuousIntegration($input)) {
            $arguments[] = '--no-interaction';
        }
        return $this->runTool($context, $input, $output, 'yaml-lint', $arguments);
    }
}
