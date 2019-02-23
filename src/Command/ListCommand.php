<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

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
        $output->writeln("List of available commands:");
        $commands = $this->getApplication()->all();

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

        $listTable = new Table($output);
        $listTable->setRows($getCommandListRows($commands));
        $listTable->setStyle("compact")->render();
    }
}
