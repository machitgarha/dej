<?php

$minRequiredPhpVersion = 7.1;
if ((float)(PHP_VERSION) <= $minRequiredPhpVersion)
    return print("Minimum required PHP version is $minRequiredPhpVersion." . PHP_EOL);

require_once __DIR__ . '/../vendor/autoload.php';

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
use Dej\Command\ListCommand;
use Dej\Component\ShellOutput;
use Dej\Exception\OutputException;
use Symfony\Component\Console\Exception\CommandNotFoundException;

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
        new ListCommand(),
    ]);

    $application->setDefaultCommand("help");
    $application->setCatchExceptions(false);

    $application->run(null, $shellOutput);
} catch (OutputException $e) {
    return $shellOutput->error($e->getMessage());
} catch (CommandNotFoundException $e) {
    return $shellOutput->error("Unknown command. See 'dej list'.");
} catch (\Throwable $e) {
    // Debugging only:
    // throw $e;
    return $shellOutput->error("Unknown error.");
}

__HALT_COMPILER();