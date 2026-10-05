<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Composer\Command;


final class FunctionalTestsCommand extends AbstractTestsCommand
{
    public function __construct()
    {
        parent::__construct('functional');
    }
}
