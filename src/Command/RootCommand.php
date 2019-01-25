<?php

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;

abstract class RootCommand extends BaseCommand
{
    public function __construct(string $name = null)
    {
        if (posix_getuid() !== 0)
            throw new \Exception("Root permission needed", 1);

        parent::__construct($name);
    }
}
