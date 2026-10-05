<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [
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
    }
}
