<?php

namespace Localheinz\GitHub\ChangeLog\Console;

use Symfony\Component\Console\Command\Command;

class ChangeLogCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('localheinz:changelog')
            ->setDescription('Creates a changelog based on references')
        ;
    }
}
