<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Configuration;

use Closure;
use DOMDocument;
use DOMElement;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class PhpUnitConfiguration
{
    public function create(ProjectContext $context, string $suite): string
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->load(__DIR__ . '/../../build/PhpUnit.xml');
        $root = $document->documentElement;
        if ($context->profile === 'project') {
            $root->setAttribute('backupGlobals', 'true');
            $root->setAttribute('cacheResult', 'false');
            $root->setAttribute('beStrictAboutTestsThatDoNotTestAnything', 'false');
        }
        $root->setAttribute('bootstrap', __DIR__ . '/../../build/phpunit-bootstrap.php');
        $root->setAttribute('cacheDirectory', $context->absolute('.phpunit.cache'));
        $suites = $root->getElementsByTagName('testsuites')->item(0);
        $tests = $document->createElement('testsuite');
        $tests->setAttribute('name', $suite);
        foreach ($context->testPaths($suite) as $path) {
            $directory = $document->createElement('directory');
            $directory->setAttribute('suffix', 'Test.php');
            $directory->appendChild($document->createTextNode($path));
            $tests->appendChild($directory);
        }
        $suites->appendChild($tests);
        $source = $document->createElement('source');
        $include = $document->createElement('include');
        foreach ($context->paths() as $path) {
            $candidates = $context->profile === 'extension' ? [$path . '/Classes'] : (glob($path . '/*/Classes', GLOB_ONLYDIR) ?: []);
            foreach ($candidates as $candidate) {
                if (is_dir($candidate)) {
                    $directory = $document->createElement('directory');
                    $directory->setAttribute('suffix', '.php');
                    $directory->appendChild($document->createTextNode($candidate));
                    $include->appendChild($directory);
                }
            }
        }
        if ($include->hasChildNodes()) {
            $source->appendChild($include);
            $root->appendChild($source);
        }
        $php = $root->getElementsByTagName('php')->item(0);
        $this->env($document, $php, 'TYPO3_CONTEXT', 'Testing');
        $this->env($document, $php, 'TYPO3_CODER_SUITE', $suite);
        if ($suite === 'functional') {
            $this->env($document, $php, 'typo3DatabaseDriver', 'pdo_sqlite');
            $this->env($document, $php, 'TYPO3_PATH_ROOT', $context->webDir);
            $this->env($document, $php, 'TYPO3_PATH_APP', $context->rootDir);
        }
        $file = $context->overrideFile('phpunit');
        if (is_file($file)) {
            $customize = require $file;
            if (!$customize instanceof Closure) {
                throw new RuntimeException($file . ' must return a callback accepting DOMDocument, ProjectContext and suite name.');
            }
            $customize($document, $context, $suite);
        }
        // Empty test suites are successful, including when a consumer tightens other options.
        $root->setAttribute('failOnEmptyTestSuite', 'false');
        $root->setAttribute('xsi:noNamespaceSchemaLocation', $context->vendorDir . '/phpunit/phpunit/phpunit.xsd');
        $schema = $context->vendorDir . '/phpunit/phpunit/phpunit.xsd';
        if (!is_file($schema) || !$document->schemaValidate($schema)) {
            throw new RuntimeException('Invalid PHPUnit configuration for the installed PHPUnit version.');
        }
        $file = tempnam(sys_get_temp_dir(), 'typo3-coder-phpunit-');
        if ($file === false) {
            throw new RuntimeException('Cannot create temporary PHPUnit configuration.');
        }
        if ($document->save($file) === false) {
            unlink($file);
            throw new RuntimeException('Cannot write temporary PHPUnit configuration.');
        }
        return $file;
    }

    public function hasTests(string $configuration): bool
    {
        $document = new DOMDocument();
        $document->load($configuration);
        $bootstrap = $document->documentElement->getAttribute('bootstrap');
        if ($bootstrap !== '' && !is_file($bootstrap)) {
            throw new RuntimeException('PHPUnit bootstrap does not exist: ' . $bootstrap);
        }
        $found = false;
        foreach ($document->getElementsByTagName('testsuite') as $suite) {
            foreach ($suite->childNodes as $path) {
                if (!$path instanceof DOMElement || !in_array($path->tagName, ['file', 'directory'], true)) {
                    continue;
                }
                $name = trim($path->textContent);
                if ($path->tagName === 'file') {
                    if (!is_file($name)) {
                        throw new RuntimeException('PHPUnit test file does not exist: ' . $name);
                    }
                    $found = true;
                    continue;
                }
                if (!is_dir($name)) {
                    throw new RuntimeException('PHPUnit test directory does not exist: ' . $name);
                }
                $suffix = $path->hasAttribute('suffix') ? $path->getAttribute('suffix') : 'Test.php';
                $prefix = $path->getAttribute('prefix');
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($name, FilesystemIterator::SKIP_DOTS));
                foreach ($files as $file) {
                    if ($file->isFile() && str_starts_with($file->getFilename(), $prefix) && str_ends_with($file->getFilename(), $suffix)) {
                        $found = true;
                        break;
                    }
                }
            }
        }
        return $found;
    }

    private function env(DOMDocument $document, DOMElement $php, string $name, string $value): void
    {
        $env = $document->createElement('env');
        $env->setAttribute('name', $name);
        $env->setAttribute('value', $value);
        if (in_array($name, ['TYPO3_CODER_SUITE', 'TYPO3_CONTEXT'], true)) {
            $env->setAttribute('force', 'true');
        }
        $php->appendChild($env);
    }
}
