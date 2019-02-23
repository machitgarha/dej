<?php

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use MAChitgarha\Component\JSONFile;
use Dej\Element\ShellOutput;
use Webmozart\PathUtil\Path;

abstract class BaseCommand extends Command
{
    protected function loadJson(string $filename, string $prefix = "config"): JSONFile
    {
        return new JSONFile(__DIR__ . "/../../$prefix/$filename.json");
    }

    protected function checkRootPermissions()
    {
        $cannotCheckMessage = "We cannot detect if root permissions granted or not. Please " .
            "make sure you granted, otherwise, processes will not work and you will have problems.";
        if (!function_exists("posix_getuid"))
            echo $cannotCheckMessage;

        if (posix_getuid() !== 0)
            throw new \Exception("Root permission needed");
    }

    protected function getHelpFromFile(string $filename, array $data = [])
    {
        $filePath = __DIR__ . "/../../data/helps/$filename.txt";
        $contents = trim(@file_get_contents($filePath));

        foreach ($data as $placeholder => $value)
            $contents = str_replace("{{$placeholder}}", $value, $contents);

        return $contents;
    }
}
