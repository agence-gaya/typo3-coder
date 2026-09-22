[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg?style=flat-square)](https://get.typo3.org/version/14)
[![ci](https://github.com/agence-gaya/typo3-coder/actions/workflows/ci.yml/badge.svg)](https://github.com/agence-gaya/typo3-hcaptcha/actions/workflows/ci.yml)
[![License](https://poser.pugx.org/gaya/typo3-coder/license)](https://packagist.org/packages/gaya/typo3-coder)

# TYPO3 coder

Shared Rector, Fractor, PHP-CS-Fixer, PHPLint, TypoScript lint and PHPUnit configuration for TYPO3 14 projects and standalone extensions.

## Installation

```sh
composer require --dev gaya/typo3-coder:^14.1
composer config allow-plugins.gaya/typo3-coder true
composer config allow-plugins.a9f/fractor-extension-installer true
```

Allow the plugins when prompted during installation. Configuration stays in this package: no generated configuration belongs in your project or `.gitignore`. Tool caches may still need to be ignored.

```sh
composer coder:rector
composer coder:fractor
composer coder:php-cs-fixer
composer coder:phplint
composer coder:typoscript-lint
composer coder:yaml-lint
composer coder:tests:unit
composer coder:tests:functional
```

Use `--continuous-integration` to check without applying changes. Arguments after `--` go to the tool, for example:

```sh
composer coder:rector --continuous-integration -- --no-progress-bar
composer coder:tests:unit --continuous-integration -- --filter MyTest --log-junit .build/logs/unit.xml
XDEBUG_MODE=coverage composer coder:tests:unit -- --coverage-clover .build/logs/clover.xml
composer --working-dir=path/to/extension coder:phplint
```

A coverage driver (Xdebug or PCOV) must be enabled to collect coverage. Tool exit codes are preserved. Missing conventional test directories and empty test suites are successful, including in CI. Explicitly configured missing directories, bootstrap errors and failing tests remain errors.

## Project context

The root Composer package determines the profile: `typo3-cms-extension` selects **extension**, other types select **project**. Dependencies' `require-dev` entries do not install coder: require it in the Composer root where commands run.

| Setting | Project default | Extension default |
| --- | --- | --- |
| Analysis paths | `packages/` | `.` |
| Unit tests | `packages/site*/Tests/Unit` | `Tests/Unit` |
| Functional tests | `packages/site*/Tests/Functional` | `Tests/Functional` |
| Vendor/binaries | Composer `vendor-dir` / `bin-dir` | Same, including `.build/vendor` / `.build/bin` |
| Web root | `extra.typo3/cms.web-dir`, otherwise `public` | Same |
| Rector PHP target | Current PHP major/minor | Current PHP major/minor |

Dependencies, public assets, build directories, caches, Git metadata and node_modules are excluded from analysis. Explicit analysis/test paths must exist. Paths and exclusions are relative to the root manifest. Test paths accept glob patterns.

Optional overrides in `composer.json`:

```json
{
  "extra": {
    "gaya/typo3-coder": {
      "profile": "extension",
      "paths": ["Classes", "Configuration", "Tests"],
      "exclude": ["Tests/Fixtures"],
      "tests": {
        "unit": ["Tests/Unit"],
        "functional": ["Tests/Functional"]
      }
    }
  }
}
```

Only include settings you need. Rector always targets the running PHP major/minor version. For a reusable extension, run Rector only in the CI job using its minimum supported PHP version; keep tests and lint on the full PHP matrix. For exemple, the Rector step is restricted to PHP 8.2:

```yaml
- name: Rector
  if: matrix.php == '8.2'
  run: composer coder:rector --continuous-integration
```

Run Rector locally with PHP 8.2. Unsupported runtime versions produce an actionable error asking you to update Rector.

## Tool customizations

Version optional files in `build/` for both TYPO3 projects and standalone extensions. These consumer files customize the shared configurations in `vendor/gaya/typo3-coder/build/`. PHP callbacks receive the default configuration and a `GAYA\Typo3Coder\Configuration\ProjectContext` with absolute `rootDir`, `vendorDir`, `binDir` and `webDir`. Mutate the configuration and return nothing, or return a replacement of the same configuration type. Defaults, including the PHP target, are applied first.

`build/rector.php`:

```php
<?php
use GAYA\Typo3Coder\Configuration\ProjectContext;
use Rector\Configuration\RectorConfigBuilder;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;

return static function (RectorConfigBuilder $config, ProjectContext $context): void {
    $config->withSkip([OptionalParametersAfterRequiredRector::class]);
};
```

Rector's builder merges sets and skips. To replace defaults completely, return a new `RectorConfig::configure()` builder and configure its paths, PHP target and rules explicitly.

`build/fractor.php` follows the same contract with `a9f\Fractor\Configuration\FractorConfigurationBuilder`.

`build/php-cs-fixer.php`:

```php
<?php
use PhpCsFixer\Config;

return static function (Config $config): void {
    $config->setRules(array_replace($config->getRules(), ['declare_strict_types' => false]));
};
```

For IDEs, run the Composer commands with the consumer as working directory. Rector, Fractor and PHP-CS-Fixer can also use the files in this package's `build/` with their `--config` option, provided the consumer autoloader is available and the working directory is the consumer root. Use `coder:phplint` and `coder:tests:*` to resolve their dynamic paths.

## TypoScript lint

`composer coder:typoscript-lint` uses `helmich/typo3-typoscript-lint` with the shared `build/tslint.yaml` shipped in this package. It scans the configured analysis paths with the same dependency/build exclusions as Rector. Supported files are `*.typoscript`, `*.tsconfig`, `setup.txt`, `constants.txt`, `ext_typoscript_setup.txt` and `ext_typoscript_constants.txt`. Legacy `*.ts` files are included only under `Configuration/TypoScript/` or `Configuration/TSconfig/` to avoid treating TypeScript as TypoScript.

The common rules use two spaces per indentation level, indent conditions, and disable `RepeatingRValue`. Errors fail the command; `--continuous-integration` additionally fails on warnings. Projects without TypoScript files succeed with an informational message.

Additional native options can be passed after `--`, for example `--format xml --output build/typoscript-report.xml`. An explicit `--config build/tslint.yaml` replaces the shared YAML with a consumer-owned native configuration; file selection still uses coder's analysis paths. No configuration is copied into the consumer.

## YAML lint

`composer coder:yaml-lint` uses Symfony's YAML linter to validate `*.yaml` and `*.yml` in the configured analysis paths, with the common dependency/build exclusions. Invalid YAML fails both locally and in CI; no YAML files is a success. No configuration or launcher is copied into the consumer.

Use `composer coder:yaml-lint --continuous-integration` in CI. Native options can be passed after `--`, for example `--format=json` or `--parse-tags` for custom YAML tags.

## PHPStan

`composer coder:phpstan` use the shared configurations and scan the consumer's PHP source files. PHPStan defaults to level 10 and accepts `extra.gaya/typo3-coder.phpstan-level`; advanced overrides belong in `build/phpstan.neon`.

`composer coder:phpstan:baseline` generate `build/phpstan.baseline.neon` in the consuming project.

## PHPUnit and TYPO3 functional tests

The package builds a temporary XML with absolute paths, validates it against the installed PHPUnit schema and removes it after execution. It supports the PHPUnit versions allowed by the installed TYPO3 testing framework. No XML is written into your repository or vendor directory.

`build/phpunit.php` returns a callback accepting `DOMDocument $document`, `ProjectContext $context` and `string $suite` (`unit` or `functional`). It can adjust PHPUnit attributes, coverage sources/exclusions, suites and environment variables. Relative paths added here must be converted with `$context->absolute()` because the XML lives in the system temporary directory.

```php
<?php
use GAYA\Typo3Coder\Configuration\ProjectContext;

return static function (DOMDocument $document, ProjectContext $context, string $suite): void {
    $document->documentElement->setAttribute('requireCoverageMetadata', 'true');
};
```

Functional tests bootstrap TYPO3's testing framework, use the Composer web root and default to `pdo_sqlite`. Install `pdo_sqlite` in CI (for `shivammathur/setup-php`, use `extensions: pdo_sqlite`). Environment variables already set by the caller take precedence over XML defaults; other database drivers must use a dedicated test database and testing-framework credentials. Never point test database settings at production data.

Minimal `Tests/Functional/PageTest.php`:

```php
<?php
namespace Vendor\Extension\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTest extends FunctionalTestCase
{
    #[Test]
    public function importsPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        self::assertSame('Test page', $connection->select(['title'], 'pages', ['uid' => 1])->fetchOne());
    }
}
```

`Tests/Functional/Fixtures/pages.csv`:

```csv
"pages"
,"uid","pid","title"
,1,0,"Test page"
```

Configure Composer `autoload-dev` for your `Tests/` namespace. The framework creates an isolated TYPO3 instance and imports fixtures into its test database. Extension-specific packages, fixtures and HTTP mocks remain in the extension's tests. The gaya/typo3-hcaptcha integration demonstrates loading an extension and testing form requests.

## Migration from 14.0

On Composer install/update, coder automatically:

* Removes the unmodified copies formerly generated as `rector.php`, `fractor.php`, `.php-cs-fixer.php`, `.phplint.yml` and `build/phpunit/PhpUnit.xml`.
* Moves `rector.project.php`, `fractor.project.php` and `.php-cs-fixer.project.php` to their corresponding `build/*.php` paths. Legacy variable scope is supported, including replacing the builder. `__DIR__` and `__FILE__` retain their old meaning after relocation. The migrated files should be committed.
* Removes exact ignore entries for the generated files, preserving cache patterns and unrelated entries.

Run `composer coder:migrate` explicitly if needed (for example after an install with scripts disabled). It is safe to run repeatedly. Conflicting customizations are reported without overwriting the destination or deleting the original. Resolve the conflict and rerun migration before using the new commands.

The previously documented `.rector.php` name was a documentation error. It is neither loaded nor migrated.

## Development and release

```sh
composer install
vendor/bin/phpunit -c Tests/phpunit.xml
```

Publish coder package first on GitHub/Packagist, then update the consuming projects' lock files. Local verification can use a temporary Composer manifest with a path repository; do not commit that repository to consumers.
