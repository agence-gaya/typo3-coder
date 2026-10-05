<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\PhpUnitConfiguration;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractTestsCommand extends AbstractCommand
{
    public function __construct(private readonly string $suite)
    {
        parent::__construct('tests:' . $suite);
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        $suite = $this->suite;
        // Validate explicit paths even if none of the conventional paths exist.
        if ($context->testPaths($suite) === [] && !is_file($context->overrideFile('phpunit'))) {
            $output->writeln('No ' . $suite . ' tests found; nothing to run.');
            return 0;
        }
        $temporary = (new PhpUnitConfiguration())->create($context, $suite);
        $arguments = ['--configuration', $temporary];
        if ($this->isContinuousIntegration($input)) {
            $arguments[] = '--no-progress';
        }
        try {
            if (!(new PhpUnitConfiguration())->hasTests($temporary)) {
                $output->writeln('No tests found; nothing to run.');
                return 0;
            }
            return $this->runTool($context, $input, $output, 'phpunit', $arguments);
        } finally {
            unlink($temporary);
        }
    }
}
