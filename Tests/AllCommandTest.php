<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Tests;

use Composer\Composer;
use Composer\Console\Application;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use GAYA\Typo3Coder\Composer\Command\AllCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException as InputException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AllCommandTest extends TestCase
{
    private const COMMANDS = [
        'coder:rector', 'coder:fractor', 'coder:php-cs-fixer', 'coder:phplint',
        'coder:typoscript-lint', 'coder:yaml-lint', 'coder:phpstan',
        'coder:tests:unit', 'coder:tests:functional',
    ];

    public function testExecutionOrderAndCiOptions(): void
    {
        foreach ([[], ['--ci' => true], ['--continuous-integration' => true], ['--ci' => true, '--continuous-integration' => true]] as $options) {
            $calls = [];
            $tester = new CommandTester($this->createCommand($calls));
            self::assertSame(0, $tester->execute($options));
            self::assertSame(self::COMMANDS, array_column($calls, 'name'));
            self::assertSame(array_fill(0, 9, $options !== []), array_column($calls, 'ci'));
            foreach (self::COMMANDS as $name) {
                self::assertStringContainsString('Running ' . $name, $tester->getDisplay());
                self::assertStringContainsString($name . ': OK', $tester->getDisplay());
            }
        }
    }

    public function testFailuresAndExceptionsDoNotStopExecution(): void
    {
        $calls = [];
        $tester = new CommandTester($this->createCommand($calls, [
            'coder:rector' => 7,
            'coder:yaml-lint' => new RuntimeException('Tool failed'),
        ]));
        self::assertSame(1, $tester->execute([]));
        self::assertSame(self::COMMANDS, array_column($calls, 'name'));
        self::assertStringContainsString('coder:rector: FAILED (exit 7)', $tester->getDisplay());
        self::assertStringContainsString('Tool failed', $tester->getDisplay());
        self::assertStringContainsString('coder:yaml-lint: FAILED (exit 1)', $tester->getDisplay());
        self::assertStringContainsString('coder:tests:functional: OK', $tester->getDisplay());
    }

    public function testGlobalOptionsAndNonInteractiveModeAreForwarded(): void
    {
        $calls = [];
        $tester = new CommandTester($this->createCommand($calls));
        self::assertSame(0, $tester->execute(['--no-interaction' => true, '--no-plugins' => true], ['interactive' => false]));
        self::assertSame(array_fill(0, 9, false), array_column($calls, 'interactive'));
        self::assertSame(array_fill(0, 9, true), array_column($calls, 'no-plugins'));
    }

    public function testToolArgumentsAreRejected(): void
    {
        $calls = [];
        $command = $this->createCommand($calls);
        $this->expectException(InputException::class);
        $command->run(new ArgvInput(['composer', 'coder:all', '--', '--filter', 'MyTest']), new BufferedOutput());
    }

    private function createCommand(array &$calls, array $results = []): AllCommand
    {
        $application = new Application();
        foreach ([...self::COMMANDS, 'coder:migrate', 'coder:phpstan:baseline'] as $name) {
            $child = new Command($name);
            $child->addOption('continuous-integration', null, InputOption::VALUE_NONE);
            $child->setCode(static function (InputInterface $input, OutputInterface $output) use (&$calls, $results, $name): int {
                $calls[] = [
                    'name' => $name,
                    'ci' => $input->getOption('continuous-integration'),
                    'interactive' => $input->isInteractive(),
                    'no-plugins' => $input->getOption('no-plugins'),
                ];
                $result = $results[$name] ?? 0;
                if ($result instanceof RuntimeException) {
                    throw $result;
                }
                return $result;
            });
            $application->add($child);
        }
        $composer = new Composer();
        $composer->setPackage(new RootPackage('gaya/test', '1.0.0.0', '1.0.0'));
        $composer->setEventDispatcher(new EventDispatcher($composer, new NullIO()));
        $command = new AllCommand();
        $command->setComposer($composer);
        $command->setIO(new NullIO());
        $application->add($command);
        return $command;
    }
}
