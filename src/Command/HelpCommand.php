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
            ->addArgument("command_name", InputArgument::OPTIONAL, "The command name", "help")
            ->setDescription("Gets Dej help.")
            ->setHelp($this->getHelpFromFile("help"))
        ;
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
        $outputHelp = function (Command $command) use ($output) {
            $help = $command->getHelp() ?? $command->getDescription();
            if ($help !== null) {
                $output->writeln($help);
                return true;
            }
            return false;
        };

        if ($this->command !== null && $outputHelp($command)) {
            return;
        }

        $commandName = $input->getArgument("command_name");
        if ($this->getApplication()->has($commandName) &&
            $outputHelp($this->getApplication()->get($commandName))) {
            return;
        }

        self::commandNotFound($output, $commandName);

        $this->command = null;
    }

    public static function commandNotFound(OutputInterface $output, string $commandName)
    {
        $output->writeln([
            "Unknown command '$commandName'.",
            "Try 'dej help' for more information."]
        );
    }
}
