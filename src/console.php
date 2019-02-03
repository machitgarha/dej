#!/usr/bin/env php
<?php

use Symfony\Component\Console\Output\ConsoleOutput;
use Dej\Command\HelpCommand;
use Dej\Command\StartCommand;
use Dej\Command\StopCommand;
use Dej\Command\StatusCommand;
use Dej\Command\RestartCommand;
use Dej\Command\CheckCommand;
use Dej\Command\ConfigCommand;

require_once __DIR__ . '/../vendor/autoload.php';

// TODO: Add a try/catch
// Create the Application
$application = new Symfony\Component\Console\Application;

$application->addCommands([
    new HelpCommand(),
    new StartCommand(),
    new StopCommand(),
    new StatusCommand(),
    new RestartCommand(),
    new CheckCommand(),
    new ConfigCommand()
]);

$application->setCatchExceptions(false);
$application->setName("Dej");

$application->run();
