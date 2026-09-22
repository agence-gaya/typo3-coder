<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Installer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use GAYA\Typo3Coder\Composer\Command\CommandProvider;
use GAYA\Typo3Coder\Configuration\ProjectContext;
use GAYA\Typo3Coder\Migration;

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
        // Composer registers EventSubscriberInterface automatically.
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Consumer-owned configuration must survive uninstall.
    }

    public function listen(Event $event): void
    {
        (new Migration())->run(ProjectContext::fromComposer($event->getComposer()), $event->getIO()->write(...));
    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => CommandProvider::class,
        ];
    }

}
