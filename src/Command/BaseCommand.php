<?php

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use MAChitgarha\Component\JSONFile;

abstract class BaseCommand extends Command
{
    protected function loadJson(string $filename, string $prefix = "config"): JSONFile
    {
        return new JSONFile(__DIR__ . "/../../$prefix/$filename.json");
    }

    protected function checkRootPermissions()
    {
        if (posix_getuid() !== 0)
            throw new \Exception("Root permission needed");
    }
}
