<?php

namespace Localheinz\GitHub\ChangeLog\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class ChangeLogCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('localheinz:changelog')
            ->setDescription('Creates a changelog based on references')
            ->addArgument(
                'vendor',
                InputArgument::REQUIRED,
                'The name of the vendor, e.g., "localheinz"'
            )
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'The name of the package, e.g. "github-changelog"'
            )
            ->addArgument(
                'start',
                InputArgument::REQUIRED,
                'The start reference, e.g. "1.0.0"'
            )
            ->addArgument(
                'end',
                InputArgument::REQUIRED,
                'The end reference, e.g. "1.1.0"'
            )
        ;
    }
}
