<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Dej\Element\ShellOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\DescriptorHelper;

/**
 * Handles helps.
 */
class HelpCommand extends BaseCommand
{
    /** @var Command The command when 'dej [command] {-h,--help}' is called. */
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

    /**
     * Sets the command when using -h or --help.
     *
     * @param Command $command The called command for help.
     * @return void
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Executes help command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, $output)
    {
        /*
         * Searches for the help of a command. If the command has help, returns it, otherwise, it
         * returns the description of the command. If the command does not have any helps (i.e.
         * either a help or a description), then it will return false. If everything goes
         * successful, it will return true.
         */
        $outputHelp = function (Command $command) use ($output) {
            $help = $command->getHelp() ?? $command->getDescription();
            if ($help !== null) {
                $output->writeln($help);
                return true;
            }
            return false;
        };

        // Handling -h or --help
        if ($this->command !== null && $outputHelp($this->command)) {
            return;
        }

        // Handling 'dej help [command]'
        $commandName = $input->getArgument("command_name");
        if ($this->getApplication()->has($commandName) &&
            $outputHelp($this->getApplication()->get($commandName))) {
            return;
        }

        self::commandNotFound($output, $commandName);

        $this->command = null;
    }

    /**
     * Prints a message when command's help couldn't be found.
     *
     * @param OutputInterface $output
     * @param string $commandName The command name.
     * @return void
     */
    public static function commandNotFound(OutputInterface $output, string $commandName)
    {
        $output->writeln([
            "Unknown command '$commandName'.",
            "Try 'dej help' for more information."
        ]);
    }
}
