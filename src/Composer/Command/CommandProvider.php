<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Composer;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use InvalidArgumentException;

final class CommandProvider implements CommandProviderCapability
{
    private readonly ?Composer $composer;

    public function __construct(array $arguments = [])
    {
        $this->composer = $arguments['composer'] ?? null;
    }

    public function getCommands(): array
    {
        $commands = [
            new RectorCommand(),
            new FractorCommand(),
            new PhpCsFixerCommand(),
            new PhpLintCommand(),
            new TypoScriptLintCommand(),
            new YamlLintCommand(),
            new PhpstanCommand(),
            new PhpstanBaselineCommand(),
            new UnitTestsCommand(),
            new FunctionalTestsCommand(),
            new MigrateCommand(),
            new AllCommand(),
        ];

        $extra = $this->composer?->getPackage()->getExtra() ?? [];
        $config = $extra['gaya/typo3-coder'] ?? [];
        if (!is_array($config)) {
            throw new InvalidArgumentException('extra.gaya/typo3-coder must be an object.');
        }
        if (!array_key_exists('command', $config)) {
            return $commands;
        }
        $groups = $config['command'];
        $path = 'extra.gaya/typo3-coder.command';
        if (!is_array($groups) || ($groups !== [] && array_is_list($groups))) {
            throw new InvalidArgumentException($path . ' must be an object mapping names to command lists.');
        }
        $reserved = array_map(static fn($command): string => substr($command->getName(), 6), $commands);
        $tools = array_values(array_diff($reserved, ['all']));
        foreach ($groups as $name => $members) {
            $groupPath = $path . '.' . $name;
            if (!is_string($name) || preg_match('/\A[a-z][a-z0-9-]*\z/', $name) !== 1) {
                throw new InvalidArgumentException($groupPath . ': group names must match [a-z][a-z0-9-]*.');
            }
            if (in_array($name, $reserved, true)) {
                throw new InvalidArgumentException($groupPath . ': this command name is reserved.');
            }
            if (!is_array($members) || !array_is_list($members) || $members === []) {
                throw new InvalidArgumentException($groupPath . ' must be a non-empty list of command names.');
            }
            $qualified = [];
            foreach ($members as $index => $member) {
                if (!is_string($member) || !in_array($member, $tools, true)) {
                    throw new InvalidArgumentException($groupPath . '.' . $index . ': expected a built-in tool name without the coder: prefix.');
                }
                if (in_array('coder:' . $member, $qualified, true)) {
                    throw new InvalidArgumentException($groupPath . '.' . $index . ': duplicate command ' . $member . '.');
                }
                $qualified[] = 'coder:' . $member;
            }
            $commands[] = new CommandGroup('coder:' . $name, 'Run configured command group ' . $name, $qualified);
        }

        return $commands;
    }
}
