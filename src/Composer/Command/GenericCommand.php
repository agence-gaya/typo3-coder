<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Command\BaseCommand;
use GAYA\Typo3Coder\Configuration\PhpUnitConfiguration;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use GAYA\Typo3Coder\Migration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class GenericCommand extends BaseCommand
{
    public function __construct(private readonly string $tool)
    {
        parent::__construct('coder:' . $tool);
        $this->setDescription($tool === 'migrate' ? 'Migrate legacy coder configurations' : 'Run ' . $tool);
        $this->addOption('continuous-integration', null, InputOption::VALUE_NONE, 'Check without applying changes');
        $this->addArgument('tool-arguments', InputArgument::IS_ARRAY, 'Arguments passed to the tool after --');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = ProjectContext::fromComposer($this->getComposer(true));
        if ($this->tool === 'migrate') {
            (new Migration())->run($context, $output->writeln(...));
            return 0;
        }
        $ci = $input->getOption('continuous-integration');
        $resources = dirname(__DIR__, 3) . '/build';
        $temporary = null;
        $arguments = [];
        $binary = $this->tool;
        if (str_starts_with($this->tool, 'tests:')) {
            $suite = substr($this->tool, 6);
            // Validate explicit paths even if none of the conventional paths exist.
            if ($context->testPaths($suite) === [] && !is_file($context->overrideFile('phpunit'))) {
                $output->writeln('No ' . $suite . ' tests found; nothing to run.');
                return 0;
            }
            $binary = 'phpunit';
            $temporary = (new PhpUnitConfiguration())->create($context, $suite);
            $arguments = ['--configuration', $temporary];
            if ($ci) {
                $arguments[] = '--no-progress';
            }
        } elseif ($this->tool === 'php-cs-fixer') {
            $arguments = ['fix', '--config', $resources . '/.php-cs-fixer.php', '--diff', '--verbose'];
            if ($ci) {
                $arguments[] = '--dry-run';
            }
        } elseif (in_array($this->tool, ['rector', 'fractor'], true)) {
            $arguments = ['process', '--config', $resources . '/' . $this->tool . '.php'];
            if ($ci) {
                $arguments[] = '--dry-run';
            }
        } elseif ($this->tool === 'phplint') {
            $arguments = ['--configuration', $resources . '/.phplint.yml'];
            foreach ([...$context->exclusions(), 'vendor', '.build', '.Build', 'Build', 'build', 'var', 'node_modules', 'templates'] as $excluded) {
                // PHPLint's Finder expects exclusions relative to each search directory.
                foreach ($context->paths() as $path) {
                    $relative = str_starts_with($excluded, rtrim($path, '/') . '/') ? substr($excluded, strlen(rtrim($path, '/')) + 1) : $excluded;
                    array_push($arguments, '--exclude', $relative);
                }
            }
            if ($ci) {
                $arguments[] = '--no-interaction';
            }
            array_push($arguments, ...$context->paths());
        }
        try {
            if ($temporary !== null && !(new PhpUnitConfiguration())->hasTests($temporary)) {
                $output->writeln('No tests found; nothing to run.');
                return 0;
            }
            $process = new Process([PHP_BINARY, $context->binDir . '/' . $binary, ...$arguments, ...$input->getArgument('tool-arguments')], $context->rootDir, ['TYPO3_CODER_CONTEXT' => $context->environment()]);
            $process->setTimeout(null);
            return $process->run(static function (string $type, string $buffer) use ($output): void {
                $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            });
        } finally {
            if ($temporary !== null) {
                unlink($temporary);
            }
        }
    }
}
