<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Symfony\Component\Console\Input\InputOption;

class CommandProvider implements CommandProviderCapability
{
    protected $commands = [
        'coder:tests:unit' => [
            'binary' => 'phpunit',
            'description' => 'Run phpunit tests',
            'arguments' => ['-c', 'build/phpunit/PhpUnit.xml'],
            'ci-arguments' => ['--no-progress', '-c', 'build/phpunit/PhpUnit.xml'],
        ],
        'coder:php-cs-fixer' => [
            'binary' => 'php-cs-fixer',
            'description' => 'Run php-cs-fixer',
            'arguments' => ['fix', '--diff', '--verbose'],
            'ci-arguments' => ['fix', '--diff', '--dry-run', '--verbose'],
        ],
        'coder:rector' => [
            'binary' => 'rector',
            'description' => 'Run rector',
            'arguments' => ['process'],
            'ci-arguments' => ['process', '--dry-run', '--ansi', '--no-progress-bar'],
        ],
        'coder:fractor' => [
            'binary' => 'fractor',
            'description' => 'Run fractor',
            'arguments' => ['process'],
            'ci-arguments' => ['process', '--dry-run', '--ansi'],
        ],
        'coder:phplint' => [
            'binary' => 'phplint',
            'description' => 'Run phplint',
            'arguments' => [],
            'ci-arguments' => ['--no-interaction', '--ansi'],
        ],
    ];

    public function getCommands()
    {
        $ciOption = new InputOption('continuous-integration', null, InputOption::VALUE_NONE, 'Run in continuous integration mode');

        $commands = [];

        foreach ($this->commands as $command => $script) {
            $command = new GenericCommand($command);
            $command->setDescription($script['description']);
            $command->setScript($script['binary'], $script['arguments'], $script['ci-arguments']);
            $command->setDefinition([$ciOption]);
            $commands[] = $command;
        }

        return $commands;
    }
}
