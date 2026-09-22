<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use RuntimeException;

final class Migration
{
    public const GENERATED = ['rector.php', 'fractor.php', '.php-cs-fixer.php', '.phplint.yml', 'build/phpunit/PhpUnit.xml'];
    private const OVERRIDES = [
        'rector.project.php' => 'rector',
        'fractor.project.php' => 'fractor',
        '.php-cs-fixer.project.php' => 'php-cs-fixer',
        'config/coder/rector.php' => 'rector',
        'config/coder/fractor.php' => 'fractor',
        'config/coder/php-cs-fixer.php' => 'php-cs-fixer',
        'config/coder/phpunit.php' => 'phpunit',
    ];

    public function run(ProjectContext $context, callable $report): void
    {
        foreach (self::OVERRIDES as $old => $new) {
            $source = $context->absolute($old);
            if (!is_file($source)) {
                continue;
            }
            $destination = $context->overrideFile($new);
            $content = $this->relocate(file_get_contents($source), $old);
            // Parse before writing or removing anything. Do not execute project code during migration.
            token_get_all($content, TOKEN_PARSE);
            if (file_exists($destination) && file_get_contents($destination) !== $content) {
                throw new RuntimeException('Coder migration conflict: ' . $destination . ' already exists. Original preserved: ' . $source);
            }
            if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0o777, true) && !is_dir(dirname($destination))) {
                throw new RuntimeException('Cannot create ' . dirname($destination));
            }
            if (!file_exists($destination)) {
                $temporary = tempnam(dirname($destination), '.migration-');
                try {
                    if (file_put_contents($temporary, $content) !== strlen($content) || !chmod($temporary, fileperms($source) & 0o777) || !rename($temporary, $destination)) {
                        throw new RuntimeException('Cannot migrate ' . $source);
                    }
                } finally {
                    if (is_file($temporary)) {
                        unlink($temporary);
                    }
                }
            }
            if (file_get_contents($destination) !== $content || !unlink($source)) {
                throw new RuntimeException('Cannot finish migration of ' . $source);
            }
            $report('Migrated ' . $old . ' to build/' . $new . '.php');
        }
        foreach (self::GENERATED as $file) {
            $path = $context->absolute($file);
            if (is_file($path) || is_link($path)) {
                if (!unlink($path)) {
                    throw new RuntimeException('Cannot remove ' . $path);
                }
                $report('Removed generated configuration ' . $file);
            }
        }
        $this->cleanGitignore($context);
    }

    private function relocate(string $content, string $old): string
    {
        $originalDirectory = dirname($old) === '.' ? '(dirname(__DIR__))' : '(dirname(__DIR__) . ' . var_export('/' . dirname($old), true) . ')';
        $result = '';
        foreach (token_get_all($content, TOKEN_PARSE) as $token) {
            $result .= is_array($token) ? match ($token[0]) {
                T_DIR => $originalDirectory,
                T_FILE => '(dirname(__DIR__) . ' . var_export('/' . $old, true) . ')',
                default => $token[1],
            } : $token;
        }
        return $result;
    }

    private function cleanGitignore(ProjectContext $context): void
    {
        $directory = $context->rootDir;
        do {
            $file = $directory . '/.gitignore';
            if (is_file($file)) {
                $prefix = ltrim(substr($context->rootDir, strlen($directory)), '/');
                $prefix = $prefix === '' ? '' : $prefix . '/';
                $patterns = [];
                foreach (self::GENERATED as $generated) {
                    $patterns[] = '/' . $prefix . $generated;
                    $patterns[] = $prefix . $generated;
                }
                $lines = file($file);
                $filtered = array_filter($lines, static fn(string $line): bool => !in_array(rtrim($line, "\r\n"), $patterns, true));
                if ($lines !== $filtered && file_put_contents($file, implode('', $filtered)) === false) {
                    throw new RuntimeException('Cannot update ' . $file);
                }
            }
            if (file_exists($directory . '/.git') || dirname($directory) === $directory) {
                break;
            }
            $directory = dirname($directory);
        } while (true);
    }
}
