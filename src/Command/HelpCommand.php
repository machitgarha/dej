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
            ->addArgument("command_name", InputArgument::OPTIONAL, "The command name", "help");
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
        $getHelp = function (string $commandName) use ($output) {
            $helpPath = "data/helps/$commandName.txt";
            if (is_readable($helpPath))
                $output->exit(file_get_contents($helpPath));
        };

        if ($this->command !== null) {
            $command = $this->command->getName();
            if ($this->command->getHelp() !== null)
                $output->exit($this->command->getHelp());
            $getHelp($command); 
        }

        $commandName = $input->getArgument("command_name");
        if ($this->getApplication()->has($commandName)) {
            $getHelp($commandName);
        }

        $output->writeln([
            "Unknown command '$commandName'.",
            "Try 'dej help' for more information."]
        );

        $this->command = null;
    }
}
