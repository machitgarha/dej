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
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $output->writeln([
            "Preparing..."
        ]);

        // Check for missing options that is not set
        $alertsCount = $this->loadConfig("config")
            ->checkEverything()
            ->outputAlerts($output, ["w" => "w", "e" => "w"])
            ->getAlertsCount();

        if ($alertsCount === 0) {
            $output->writeln("Good!");
        }
    }
}
