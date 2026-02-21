<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Installer;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use GAYA\Typo3Coder\Composer\Command\CommandProvider;
use ReflectionClass;

class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => ['listen'],
            ScriptEvents::POST_UPDATE_CMD => ['listen'],
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $composer->getEventDispatcher()->addSubscriber($this);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $baseDir = $this->extractBaseDir($composer->getConfig());

        $filesystem = new Filesystem();

        foreach ([$baseDir . '/.php-cs-fixer.php', $baseDir . '/.phplint.yml', $baseDir . '/fractor.php', $baseDir . '/rector.php', $baseDir . '/build/phpunit/PhpUnit.xml'] as $filename) {
            $filesystem->remove(__DIR__ . '/../../../res/' . $filename);
        }
    }

    public function listen(Event $event): void
    {
        $baseDir = $this->extractBaseDir($event->getComposer()->getConfig());

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($baseDir . '/build/phpunit');
        $filesystem->safeCopy(__DIR__ . '/../../../res/PhpUnit.xml', $baseDir . '/build/phpunit/PhpUnit.xml');

        foreach (['.php-cs-fixer.php', '.phplint.yml', 'fractor.php', 'rector.php'] as $filename) {
            $filesystem->safeCopy(__DIR__ . '/../../../res/' . $filename, $baseDir . '/' . $filename);
        }
    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => CommandProvider::class,
        ];
    }

    protected function extractBaseDir(Config $config)
    {
        $reflectionClass = new ReflectionClass($config);
        $reflectionProperty = $reflectionClass->getProperty('baseDir');
        return $reflectionProperty->getValue($config);
    }
}
