<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use GAYA\Typo3Coder\Migration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('migrate');
        $this->setDescription('Migrate legacy coder configurations');
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        (new Migration())->run($context, $output->writeln(...));
        return 0;
    }
}
