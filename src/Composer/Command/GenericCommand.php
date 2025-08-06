<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenericCommand extends BaseCommand
{
    protected string $script;
    protected array $arguments;
    protected array $ciArguments;

    public function setScript(string $script, array $arguments = [], array $ciArguments = []): void
    {
        $this->script = $script;
        $this->arguments = $arguments;
        $this->ciArguments = $ciArguments;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $composer = $this->getComposer(true);

        $dispatcher = $composer->getEventDispatcher();
        $dispatcher->addListener('__exec_command', $this->script);

        if ($input->getOption('continuous-integration')) {
            $dispatcher->dispatchScript('__exec_command', true, $this->ciArguments);;
        } else {
            $dispatcher->dispatchScript('__exec_command', true, $this->arguments);;
        }

        return 0;
    }
}
