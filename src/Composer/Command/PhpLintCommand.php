<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;

use GAYA\Typo3Coder\Configuration\ProjectContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PhpLintCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('phplint');
    }

    protected function executeTool(ProjectContext $context, InputInterface $input, OutputInterface $output): int
    {
        $arguments = ['--configuration', $this->resources() . '/.phplint.yml'];
        foreach ([...$context->exclusions(), 'vendor', '.build', '.Build', 'Build', 'build', 'var', 'node_modules', 'templates'] as $excluded) {
            // PHPLint's Finder expects exclusions relative to each search directory.
            foreach ($context->paths() as $path) {
                $relative = str_starts_with($excluded, rtrim($path, '/') . '/') ? substr($excluded, strlen(rtrim($path, '/')) + 1) : $excluded;
                array_push($arguments, '--exclude', $relative);
            }
        }
        if ($this->isContinuousIntegration($input)) {
            $arguments[] = '--no-interaction';
        }
        array_push($arguments, ...$context->paths());
        return $this->runTool($context, $input, $output, 'phplint', $arguments);
    }
}
