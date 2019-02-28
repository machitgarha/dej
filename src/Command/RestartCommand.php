<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Dej\Component\ShellOutput;

/**
 * Restarts Dej.
 */
class RestartCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("restart")
            ->setDescription("Restarts Dej.")
            ->setHelp($this->getHelpFromFile("restart"))
        ;
    }

    /**
     * Executes restart command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     * @throws \Exception If something goes wrong.
     */
    protected function execute(InputInterface $input, $output)
    {
        $this->forceRootPermissions($output);

        $output->writeln("Restarting Dej...");

        $dej = $this->getApplication();

        // Run a start command followed by a stop command
        try {
            $args = new ArrayInput([]);
            $nullOutput = new NullOutput();
            $stopResult = $dej->find("stop")->run($args, $nullOutput);
            $startResult = $dej->find("start")->run($args, $nullOutput);
        } catch (\Throwable $e) {}

        // Check for errors and badnesses during the processes
        if (!isset($stopResult, $startResult) || $stopResult !== 0 || $startResult !== 0)
            throw new \Exception("Cannot restart Dej");

        $output->writeln("Done!");
    }
}
