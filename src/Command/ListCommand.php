<?php
/**
 * Dej command files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Dej\Component\ShellOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all commands.
 */
class ListCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDescription("Lists the commands.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $output->writeln("List of available commands:");
        $commands = $this->getApplication()->all();

        /*
         * Returns data for commands for a table, that contains command names and their
         * descriptions.
         */
        $getCommandListRows = function ($commands) {
            $rows = [];
            foreach ($commands as $command) {
                $rows[] = [
                    "",
                    $command->getName(),
                    $command->getDescription()
                ];
            }
            return $rows;
        };

        // Create a compact table of commands with their descriptions
        $listTable = new Table($output);
        $listTable->setRows($getCommandListRows($commands));
        $listTable->setStyle("compact")->render();
    }
}
