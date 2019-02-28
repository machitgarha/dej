#!/usr/bin/env php
<?php

use Dej\Component\Application;
use Dej\Command\HelpCommand;
use Dej\Command\StartCommand;
use Dej\Command\StopCommand;
use Dej\Command\StatusCommand;
use Dej\Command\RestartCommand;
use Dej\Command\CheckCommand;
use Dej\Command\ConfigCommand;
use Dej\Command\UninstallCommand;
use Dej\Command\InstallCommand;
use Dej\Command\UpdateCommand;
use Dej\Component\ShellOutput;
use Dej\Command\ListCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

require_once __DIR__ . '/../vendor/autoload.php';

$shellOutput = new ShellOutput();

try {
    // Create the Application
    $application = new Application("Dej");

    $application->addCommands([
        new HelpCommand(),
        new StartCommand(),
        new StopCommand(),
        new StatusCommand(),
        new RestartCommand(),
        new CheckCommand(),
        new ConfigCommand(),
        new UninstallCommand(),
        new InstallCommand(),
        new UpdateCommand(),
        new ListCommand(),
    ]);

    $application->setCatchExceptions(false);

    $application->run(null, $shellOutput);
} catch (\Throwable $e) {
    $shellOutput->error($e->getMessage());
}
