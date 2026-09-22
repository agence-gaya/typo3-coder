<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Tests;

use Composer\InstalledVersions;
use DOMDocument;
use FilesystemIterator;
use GAYA\Typo3Coder\Configuration\Overrides;
use GAYA\Typo3Coder\Configuration\PhpUnitConfiguration;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use GAYA\Typo3Coder\Migration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use stdClass;

final class ConfigurationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/coder tests ' . bin2hex(random_bytes(6));
        mkdir($this->root);
        mkdir($this->root . '/.git');
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testExtensionPathsAndRuntimeVersion(): void
    {
        $context = new ProjectContext($this->root, ['type' => 'typo3-cms-extension', 'config' => ['vendor-dir' => '.build/vendor', 'bin-dir' => '.build/bin']]);
        self::assertSame([$this->root], $context->paths());
        self::assertSame($this->root . '/.build/vendor', $context->vendorDir);
        self::assertSame($this->root . '/.build/bin', $context->binDir);
        self::assertSame(PHP_MAJOR_VERSION * 10000 + PHP_MINOR_VERSION * 100, $context->phpVersion());
        self::assertSame([], $context->testPaths('unit'));
        self::assertSame([], $context->testPaths('functional'));
    }

    public function testRectorFilesExcludeNestedBuildAndCustomVendorDirectories(): void
    {
        foreach (['Classes', '.build/packages', 'deps', 'packages/example/.build', 'packages/example/Classes', 'node_modules'] as $directory) {
            mkdir($this->root . '/' . $directory, 0o777, true);
            file_put_contents($this->root . '/' . $directory . '/Example.php', '<?php');
        }
        $context = new ProjectContext($this->root, ['type' => 'typo3-cms-extension', 'config' => ['vendor-dir' => 'deps']]);
        self::assertSame([
            $this->root . '/Classes/Example.php',
            $this->root . '/packages/example/Classes/Example.php',
        ], $context->phpFiles());
    }

    public function testTypoScriptDiscoveryExcludesDependenciesAndTypeScript(): void
    {
        $extension = new ProjectContext($this->root, ['type' => 'typo3-cms-extension']);
        self::assertSame([], $extension->typoScriptFiles());
        $included = [
            'packages/site/Configuration/TypoScript/setup.typoscript',
            'packages/site/Configuration/TypoScript/legacy.ts',
            'packages/site/Configuration/TSconfig/Page.tsconfig',
            'packages/site/Configuration/TypoScript/constants.txt',
            'packages/site/ext_typoscript_setup.txt',
        ];
        $excluded = [
            'packages/site/Resources/Public/main.ts',
            'packages/site/README.txt',
            'packages/site/.build/vendor/setup.typoscript',
            'packages/site/node_modules/setup.typoscript',
            'packages/site/vendor/setup.typoscript',
        ];
        foreach ([...$included, ...$excluded] as $file) {
            $path = $this->root . '/' . $file;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o777, true);
            }
            file_put_contents($path, 'page = PAGE');
        }
        $expected = array_map($extension->absolute(...), $included);
        self::assertEqualsCanonicalizing($expected, $extension->typoScriptFiles());
        self::assertEqualsCanonicalizing($expected, (new ProjectContext($this->root, []))->typoScriptFiles());
    }

    public function testProjectTestPaths(): void
    {
        mkdir($this->root . '/packages/site/Tests/Unit', 0o777, true);
        $context = new ProjectContext($this->root, []);
        self::assertSame([$this->root . '/packages/site/Tests/Unit'], $context->testPaths('unit'));
    }

    public function testExplicitMissingTestsAreAnError(): void
    {
        $context = new ProjectContext($this->root, ['extra' => ['gaya/typo3-coder' => ['tests' => ['unit' => ['missing']]]]]);
        $this->expectException(InvalidArgumentException::class);
        $context->testPaths('unit');
    }

    public function testMigrationPreservesScopePathsAndIsIdempotent(): void
    {
        $context = new ProjectContext($this->root, []);
        foreach (Migration::GENERATED as $file) {
            if (!is_dir(dirname($context->absolute($file)))) {
                mkdir(dirname($context->absolute($file)), 0o777, true);
            }
            file_put_contents($context->absolute($file), 'generated');
        }
        file_put_contents($this->root . '/.rector.php', 'not a coder file');
        file_put_contents($this->root . '/.gitignore', "/rector.php\n/.php-cs-fixer.php\n/.php-cs-fixer.cache\n/other\n");
        file_put_contents($this->root . '/rector.project.php', '<?php $rectorConfigBuilder = new \\stdClass(); $rectorConfigBuilder->root = __DIR__; $rectorConfigBuilder->file = __FILE__; $rectorConfigBuilder->literal = "__DIR__";');
        $migration = new Migration();
        $migration->run($context, static function (): void {});
        $migration->run($context, static function (): void {});
        $result = Overrides::apply('rector', new stdClass(), $context);
        self::assertSame($this->root, $result->root);
        self::assertSame($this->root . '/rector.project.php', $result->file);
        self::assertSame('__DIR__', $result->literal);
        self::assertFileDoesNotExist($this->root . '/rector.project.php');
        self::assertFileExists($this->root . '/.rector.php');
        foreach (Migration::GENERATED as $file) {
            self::assertFileDoesNotExist($context->absolute($file));
        }
        self::assertSame("/.php-cs-fixer.cache\n/other\n", file_get_contents($this->root . '/.gitignore'));
    }

    public function testIntermediateOverridesMoveToBuildAndKeepOriginalPaths(): void
    {
        mkdir($this->root . '/config/coder', 0o777, true);
        foreach (['rector', 'fractor', 'php-cs-fixer', 'phpunit'] as $tool) {
            file_put_contents($this->root . '/config/coder/' . $tool . '.php', '<?php return static function (\\stdClass $config): void { $config->root = dirname(__DIR__, 2); $config->directory = __DIR__; $config->file = __FILE__; };');
            chmod($this->root . '/config/coder/' . $tool . '.php', 0o644);
        }
        $context = new ProjectContext($this->root, []);
        $migration = new Migration();
        $migration->run($context, static function (): void {});
        $migration->run($context, static function (): void {});
        foreach (['rector', 'fractor', 'php-cs-fixer', 'phpunit'] as $tool) {
            self::assertFileDoesNotExist($this->root . '/config/coder/' . $tool . '.php');
            self::assertFileExists($context->overrideFile($tool));
            self::assertSame(0o644, fileperms($context->overrideFile($tool)) & 0o777);
        }
        $result = Overrides::apply('rector', new stdClass(), $context);
        self::assertSame($this->root, $result->root);
        self::assertSame($this->root . '/config/coder', $result->directory);
        self::assertSame($this->root . '/config/coder/rector.php', $result->file);
    }

    public function testMigrationConflictPreservesSource(): void
    {
        mkdir($this->root . '/build', 0o777, true);
        file_put_contents($this->root . '/rector.project.php', '<?php $rectorConfigBuilder->legacy = true;');
        file_put_contents($this->root . '/build/rector.php', '<?php return static fn ($config) => $config;');
        try {
            (new Migration())->run(new ProjectContext($this->root, []), static function (): void {});
            self::fail('Expected a migration conflict');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('conflict', $exception->getMessage());
            self::assertFileExists($this->root . '/rector.project.php');
        }
    }

    public function testTypedCallbackCanReplaceConfiguration(): void
    {
        mkdir($this->root . '/build', 0o777, true);
        file_put_contents($this->root . '/build/fractor.php', '<?php return static function (\\stdClass $config, \\GAYA\\Typo3Coder\\Configuration\\ProjectContext $context): \\stdClass { return (object) ["root" => $context->rootDir]; };');
        $initial = new stdClass();
        $result = Overrides::apply('fractor', $initial, new ProjectContext($this->root, []));
        self::assertNotSame($initial, $result);
        self::assertSame($this->root, $result->root);
    }

    public function testPhpunitPathsAndSchemaWithCustomVendor(): void
    {
        mkdir($this->root . '/Tests/Unit', 0o777, true);
        mkdir($this->root . '/Classes', 0o777, true);
        $vendor = dirname(InstalledVersions::getInstallPath('phpunit/phpunit'), 2);
        $context = new ProjectContext($this->root, ['type' => 'typo3-cms-extension', 'config' => ['vendor-dir' => $vendor]]);
        mkdir($this->root . '/build');
        file_put_contents($context->overrideFile('phpunit'), '<?php return static function (\\DOMDocument $document): void { $document->documentElement->setAttribute("requireCoverageMetadata", "true"); };');
        $file = (new PhpUnitConfiguration())->create($context, 'unit');
        try {
            $document = new DOMDocument();
            $document->load($file);
            self::assertSame('false', $document->documentElement->getAttribute('failOnEmptyTestSuite'));
            self::assertSame('true', $document->documentElement->getAttribute('requireCoverageMetadata'));
            self::assertStringContainsString($this->root . '/Tests/Unit', $document->saveXML());
            self::assertTrue($document->schemaValidate($vendor . '/phpunit/phpunit/phpunit.xsd'));
        } finally {
            unlink($file);
        }
    }
}
