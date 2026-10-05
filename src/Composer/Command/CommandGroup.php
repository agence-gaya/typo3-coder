<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class CommandGroup extends BaseCommand
{
    /** @param list<string> $commands Fully qualified command names. */
    public function __construct(string $name, string $description, private readonly array $commands)
    {
        parent::__construct($name);
        $this->setDescription($description);
        $this->addOption('continuous-integration', null, InputOption::VALUE_NONE, 'Check without applying changes');
        $this->addOption('ci', null, InputOption::VALUE_NONE, 'Alias for --continuous-integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        $options = [];
        foreach ($application->getDefinition()->getOptions() as $name => $option) {
            if ($input->hasOption($name) && $input->getOption($name) !== $option->getDefault()) {
                $options['--' . $name] = $input->getOption($name);
            }
        }
        if ($input->getOption('ci') || $input->getOption('continuous-integration')) {
            $options['--continuous-integration'] = true;
        }

        $results = [];
        foreach ($this->commands as $name) {
            $output->writeln(['', '<info>Running ' . $name . '</info>']);
            try {
                $commandInput = new ArrayInput(['command' => $name, ...$options]);
                $commandInput->setInteractive($input->isInteractive());
                $results[$name] = $application->find($name)->run($commandInput, $output);
            } catch (Throwable $exception) {
                $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
                $results[$name] = self::FAILURE;
            }
        }

        $output->writeln(['', '<info>Summary</info>']);
        foreach ($results as $name => $status) {
            $output->writeln($name . ': ' . ($status === self::SUCCESS ? 'OK' : 'FAILED (exit ' . $status . ')'));
        }

        return count(array_filter($results)) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
