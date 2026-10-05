<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PhpCsFixerCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('php-cs-fixer');
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        $arguments = ['fix', '--config', $this->resources() . '/.php-cs-fixer.php', '--diff', '--verbose'];
        if ($this->isContinuousIntegration($input)) {
            $arguments[] = '--dry-run';
        }
        return $this->runTool($context, $input, $output, 'php-cs-fixer', $arguments);
    }
}
