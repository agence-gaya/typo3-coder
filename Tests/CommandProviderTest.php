<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Tests;

use Composer\Composer;
use Composer\Package\RootPackage;
use GAYA\Typo3Coder\Composer\Command\CommandProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CommandProviderTest extends TestCase
{
    public function testRootConfigurationAddsGroupsAndPreservesBuiltIns(): void
    {
        $builtIns = (new CommandProvider())->getCommands();
        foreach ([[], ['gaya/typo3-coder' => ['command' => []]]] as $extra) {
            self::assertEquals($builtIns, $this->provider($extra)->getCommands());
        }
        $commands = $this->provider(['gaya/typo3-coder' => ['command' => [
            'run' => ['rector', 'fractor'],
            'maintenance' => ['migrate', 'phpstan:baseline'],
        ]]])->getCommands();
        self::assertEquals($builtIns, array_slice($commands, 0, count($builtIns)));
        self::assertSame(['coder:run', 'coder:maintenance'], array_map(static fn($command) => $command->getName(), array_slice($commands, count($builtIns))));
    }

    public function testInvalidConfigurationsAreRejectedWithTheirPath(): void
    {
        $invalid = [
            [null, 'command'],
            ['run', 'command'],
            [['rector'], 'command'],
            [['Run' => ['rector']], 'command.Run'],
            [['bad:name' => ['rector']], 'command.bad:name'],
            [['all' => ['rector']], 'command.all'],
            [['rector' => ['fractor']], 'command.rector'],
            [['run' => []], 'command.run'],
            [['run' => 'rector'], 'command.run'],
            [['run' => ['tool' => 'rector']], 'command.run'],
            [['run' => ['unknown']], 'command.run.0'],
            [['run' => ['coder:rector']], 'command.run.0'],
            [['run' => ['all']], 'command.run.0'],
            [['run' => ['run']], 'command.run.0'],
            [['run' => ['checks'], 'checks' => ['rector']], 'command.run.0'],
            [['run' => [42]], 'command.run.0'],
            [['run' => ['rector', 'rector']], 'command.run.1'],
        ];
        foreach ($invalid as [$groups, $path]) {
            try {
                $this->provider(['gaya/typo3-coder' => ['command' => $groups]])->getCommands();
                self::fail('Invalid configuration was accepted: ' . json_encode($groups));
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('extra.gaya/typo3-coder.' . $path, $exception->getMessage());
            }
        }
    }

    private function provider(array $extra): CommandProvider
    {
        $composer = new Composer();
        $package = new RootPackage('gaya/consumer', '1.0.0.0', '1.0.0');
        $package->setExtra($extra);
        $composer->setPackage($package);
        return new CommandProvider(['composer' => $composer]);
    }
}
