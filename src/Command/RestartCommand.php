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
use Dej\Component\ShellOutput;
use Dej\Exception\OutputException;

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
     * @throws OutputException If something goes wrong.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $this->forceRootPermissions($output);

        $output->writeln("Restarting Dej...");

        $dej = $this->getApplication();

        // Run a start command followed by a stop command
        try {
            $args = new ArrayInput([]);
            $output->disableOutput();
            $stopResult = $dej->find("stop")->run($args, $output);
            $startResult = $dej->find("start")->run($args, $output);
        } catch (\Throwable $e) {
        }

        $output->enableOutput();

        // Check for errors and badnesses during the processes
        if (!isset($stopResult, $startResult) || $stopResult !== 0 || $startResult !== 0) {
            throw new OutputException("Cannot restart Dej.");
        }

        $output->writeln("Done!");
    }
}
