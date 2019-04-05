<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Dej\Component\ShellOutput;

/**
 * Validates configuration files.
 */
class CheckCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("check")
            ->setDescription("Checks configuration files to be valid.")
        ;
    }

    /**
     * Executes check command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, $output)
    {
        $output->writeln([
            "Preparing..."
        ]);

        // Check for missing options that is not set
        $alertsCount = $this->loadJson("config")
            ->checkEverything()
            ->outputAlerts($output, ["w" => "w", "e" => "w"])
            ->getAlertsCount();

        if ($alertsCount === 0)
            $output->writeln("Good!");
    }
}
