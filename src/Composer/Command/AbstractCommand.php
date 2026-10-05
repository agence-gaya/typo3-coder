<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Command\BaseCommand;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends BaseCommand
{
    public function __construct(string $tool)
    {
        parent::__construct('coder:' . $tool);
        $this->setDescription('Run ' . $tool);
        $this->addOption('continuous-integration', null, InputOption::VALUE_NONE, 'Check without applying changes');
        $this->addOption('ci', null, InputOption::VALUE_NONE, 'Alias for --continuous-integration');
        $this->addArgument('tool-arguments', InputArgument::IS_ARRAY, 'Arguments passed to the tool after --');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->executeTool(ProjectContext::fromComposer($this->getComposer(true)), $input, $output);
    }

    abstract protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int;

    protected function isContinuousIntegration(InputInterface $input): bool
    {
        return $input->getOption('continuous-integration') || $input->getOption('ci');
    }

    protected function resources(): string
    {
        return dirname(__DIR__, 3) . '/build';
    }

    /** @param list<string> $arguments */
    protected function runTool(ProjectContext $context, InputInterface $input, OutputInterface $output, string $binary, array $arguments): int
    {
        $process = new Process([PHP_BINARY, $context->binDir . '/' . $binary, ...$arguments, ...$input->getArgument('tool-arguments')], $context->rootDir, ['TYPO3_CODER_CONTEXT' => $context->environment()]);
        $process->setTimeout(null);
        return $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });
    }
}
