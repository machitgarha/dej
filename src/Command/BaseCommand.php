<?php

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use Dej\Element\Shell;
use MAChitgarha\Component\JSONFile;

abstract class BaseCommand extends Command
{
    /** @var Shell */
    protected $sh;

    public function __construct(string $name = null)
    {
        $this->sh = new Shell();
        parent::__construct($name);
    }

    protected function loadConfiguration(string $filename)
    {
        return new JSONFile(__DIR__ . "/../../config/$filename.json");
    }

    protected function checkRootPermissions()
    {
        if (posix_getuid() !== 0)
            throw new \Exception("Root permission needed");
    }
}
