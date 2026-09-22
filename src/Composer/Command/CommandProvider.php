<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return array_map(static fn(string $tool): GenericCommand => new GenericCommand($tool), [
            'rector', 'fractor', 'php-cs-fixer', 'phplint', 'typoscript-lint', 'yaml-lint', 'tests:unit', 'tests:functional', 'migrate',
        ]);
    }
}
