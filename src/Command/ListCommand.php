<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        foreach ($commands as $command) {
            $output->write([
                "  ",
                $command->getName()
            ]);
            $output->writeln("");
        }
    }
}
