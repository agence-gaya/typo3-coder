<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Tests;

use FilesystemIterator;
use GAYA\Typo3Coder\Composer\Command\CommandProvider;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class CommandTest extends TestCase
{
    public function testDedicatedCommandsAndToolExecution(): void
    {
        $commands = (new CommandProvider())->getCommands();
        self::assertSame([
            'coder:rector', 'coder:fractor', 'coder:php-cs-fixer', 'coder:phplint',
            'coder:typoscript-lint', 'coder:yaml-lint', 'coder:phpstan',
            'coder:phpstan:baseline', 'coder:tests:unit', 'coder:tests:functional', 'coder:migrate',
        ], array_map(static fn($command) => $command->getName(), $commands));
        self::assertCount(11, array_unique(array_map(static fn($command) => $command::class, $commands)));

        $root = sys_get_temp_dir() . '/coder commands ' . bin2hex(random_bytes(6));
        mkdir($root);
        mkdir($root . '/bin');
        file_put_contents($root . '/example.php', '<?php');
        file_put_contents($root . '/example.yaml', 'value: true');
        file_put_contents($root . '/example.typoscript', 'page = PAGE');
        $context = new ProjectContext($root, ['type' => 'typo3-cms-extension', 'config' => ['bin-dir' => 'bin']]);
        try {
            foreach (array_slice($commands, 0, 8) as $command) {
                $tool = substr($command->getName(), 6);
                $binary = $tool === 'phpstan:baseline' ? 'phpstan' : $tool;
                file_put_contents($root . '/bin/' . $binary, '<?php echo json_encode([array_slice($argv, 1), getcwd(), getenv("TYPO3_CODER_CONTEXT")]); exit(7);');
                foreach ([[], ['--continuous-integration'], ['--ci'], ['--continuous-integration', '--ci']] as $options) {
                    $input = new ArgvInput(['coder', ...$options, '--', '--example', 'a b'], $command->getDefinition());
                    $output = new BufferedOutput();
                    $status = (new ReflectionMethod($command, 'executeTool'))->invoke($command, $context, $input, $output);
                    self::assertSame(7, $status);
                    [$arguments, $cwd, $environment] = json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR);
                    self::assertSame($root, $cwd);
                    self::assertSame($context->environment(), $environment);
                    self::assertSame(['--example', 'a b'], array_slice($arguments, -2));
                    if (in_array($tool, ['rector', 'fractor', 'php-cs-fixer'], true)) {
                        self::assertSame($options !== [], in_array('--dry-run', $arguments, true));
                        self::assertSame($tool === 'php-cs-fixer' ? 'fix' : 'process', $arguments[0]);
                    }
                    if (in_array($tool, ['phplint', 'yaml-lint'], true)) {
                        self::assertSame($options !== [], in_array('--no-interaction', $arguments, true));
                    }
                    if ($binary === 'phpstan') {
                        self::assertSame('analyze', $arguments[0]);
                        self::assertSame($tool === 'phpstan:baseline', in_array('--generate-baseline', $arguments, true));
                        self::assertFileExists($arguments[2]);
                        unlink($arguments[2]);
                        unlink(substr($arguments[2], 0, -5));
                    }
                }
            }
            foreach (array_slice($commands, 8, 2) as $command) {
                $input = new ArgvInput(['coder', '--ci'], $command->getDefinition());
                $output = new BufferedOutput();
                self::assertSame(0, (new ReflectionMethod($command, 'executeTool'))->invoke($command, $context, $input, $output));
                self::assertStringContainsString('tests found; nothing to run.', $output->fetch());
            }
        } finally {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($root);
        }
    }
}
