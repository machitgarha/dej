<?php

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\DescriptorHelper;

class HelpCommand extends BaseCommand
{
    /** @var Command */
    private $command;

    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('help')
            ->setDefinition(array(
                new InputArgument('command_name', InputArgument::OPTIONAL, 'The command name', 'help'),
            ));
    }

    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->command !== null) {
            $command = $this->command->getName();
            $helpPath = "data/helps/$command.txt";
            if (is_readable($helpPath))
                $this->sh->exit(file_get_contents($helpPath));
        }

        $command = $input->getArgument("command_name");
        $this->sh->echo("Unknown command '$command'.");
        $this->sh->echo("Try 'dej help' for more information.");

        $this->command = null;
    }
}