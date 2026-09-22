<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Configuration;

use Composer\Composer;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

final class ProjectContext
{
    public readonly string $vendorDir;
    public readonly string $binDir;
    public readonly string $webDir;
    public readonly string $profile;
    public readonly array $options;

    public function __construct(public readonly string $rootDir, array $manifest, array $composerConfig = [])
    {
        $config = array_replace($manifest['config'] ?? [], $composerConfig);
        $this->options = $manifest['extra']['gaya/typo3-coder'] ?? [];
        $this->profile = $this->options['profile'] ?? (($manifest['type'] ?? '') === 'typo3-cms-extension' ? 'extension' : 'project');
        if (!in_array($this->profile, ['project', 'extension'], true)) {
            throw new InvalidArgumentException('Coder profile must be project or extension.');
        }
        $this->vendorDir = $this->absolute($config['vendor-dir'] ?? 'vendor');
        $this->binDir = $this->absolute($config['bin-dir'] ?? $this->vendorDir . '/bin');
        $this->webDir = $this->absolute($manifest['extra']['typo3/cms']['web-dir'] ?? 'public');
    }

    public static function fromComposer(Composer $composer): self
    {
        return new self(getcwd(), [
            'type' => $composer->getPackage()->getType(),
            'extra' => $composer->getPackage()->getExtra(),
        ], [
            'vendor-dir' => $composer->getConfig()->get('vendor-dir'),
            'bin-dir' => $composer->getConfig()->get('bin-dir'),
        ]);
    }

    public static function current(): self
    {
        $serialized = getenv('TYPO3_CODER_CONTEXT');
        if ($serialized !== false && $serialized !== '') {
            $data = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
            return new self($data['root'], $data['manifest'], $data['config']);
        }
        $root = getcwd();
        $file = getenv('COMPOSER') ?: 'composer.json';
        $manifest = json_decode(file_get_contents((str_starts_with($file, '/') ? $file : $root . '/' . $file)), true, 512, JSON_THROW_ON_ERROR);
        return new self($root, $manifest);
    }

    public function environment(): string
    {
        return json_encode([
            'root' => $this->rootDir,
            'manifest' => ['extra' => ['gaya/typo3-coder' => $this->options, 'typo3/cms' => ['web-dir' => $this->webDir]], 'type' => $this->profile === 'extension' ? 'typo3-cms-extension' : 'project'],
            'config' => ['vendor-dir' => $this->vendorDir, 'bin-dir' => $this->binDir],
        ], JSON_THROW_ON_ERROR);
    }

    public function absolute(string $path): string
    {
        $path = str_starts_with($path, '/') || preg_match('~^[A-Za-z]:[\\\\/]~', $path) ? $path : $this->rootDir . '/' . $path;
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
            } else {
                $segments[] = $segment;
            }
        }
        return (str_starts_with($path, '/') ? '/' : '') . implode('/', $segments);
    }

    public function paths(): array
    {
        $paths = $this->options['paths'] ?? [$this->profile === 'extension' ? '.' : 'packages'];
        return $this->existingDirectories($paths, true);
    }

    /**
     * Resolve files before passing paths to Rector: PHPStan otherwise scans excluded vendor trees.
     */
    public function phpFiles(): array
    {
        $files = [];
        foreach ($this->paths() as $path) {
            $exclude = ['vendor', 'node_modules', 'NodeModules', 'BowerComponents', 'bower_components', '.build', '.Build', 'Build', 'build', 'var'];
            foreach ($this->exclusions() as $excluded) {
                if (str_starts_with($excluded, $path . '/')) {
                    $exclude[] = substr($excluded, strlen($path) + 1);
                }
            }
            $finder = (new Finder())->files()->name('*.php')->in($path)->exclude($exclude)->sortByName();
            foreach ($finder as $file) {
                $files[] = $file->getPathname();
            }
        }
        return array_values(array_unique($files));
    }

    public function testPaths(string $suite): array
    {
        $configured = $this->options['tests'][$suite] ?? null;
        $suffix = $suite === 'unit' ? 'Unit' : 'Functional';
        return $this->existingDirectories($configured ?? [$this->profile === 'extension' ? 'Tests/' . $suffix : 'packages/site*/Tests/' . $suffix], $configured !== null);
    }

    private function existingDirectories(array $patterns, bool $required): array
    {
        $paths = [];
        foreach ($patterns as $pattern) {
            $matches = array_values(array_filter(glob($this->absolute($pattern), GLOB_ONLYDIR) ?: [], 'is_dir'));
            if ($required && $matches === []) {
                throw new InvalidArgumentException('Configured directory does not exist: ' . $pattern);
            }
            array_push($paths, ...$matches);
        }
        return array_values(array_unique($paths));
    }

    public function exclusions(): array
    {
        return array_values(array_unique([
            $this->vendorDir, $this->webDir,
            ...array_map($this->absolute(...), ['.build', '.Build', 'Build', 'build', 'var', '.git', '.github']),
            ...array_map($this->absolute(...), $this->options['exclude'] ?? []),
        ]));
    }

    public function overrideFile(string $tool): string
    {
        return $this->absolute('build/' . $tool . '.php');
    }

    public function phpVersion(): int
    {
        return PHP_MAJOR_VERSION * 10000 + PHP_MINOR_VERSION * 100;
    }
}
