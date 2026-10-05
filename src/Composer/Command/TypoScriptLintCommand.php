<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TypoScriptLintCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('typoscript-lint');
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        $files = $context->typoScriptFiles();
        if ($files === []) {
            $output->writeln('No TypoScript files found; nothing to run.');
            return 0;
        }
        $arguments = ['--config', $this->resources() . '/tslint.yaml'];
        array_push($arguments, ...$files);
        return $this->runTool($context, $input, $output, 'typoscript-lint', $arguments);
    }
}
