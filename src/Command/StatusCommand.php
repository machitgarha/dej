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
use Dej\Component\ShellOutput;

/**
 * Gets Dej status.
 */
class StatusCommand extends BaseCommand
{
    /** @var int Number of screens when Dej is running successfully. */
    const SCREEN_NUMBER = 4;

    const STATUS_STOPPED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_PARTIAL = 2;
    const STATUS_OVERFLOW = 3;

    protected function configure()
    {
        $this
            ->setName("status")
            ->setDescription("Tells how Dej is running (i.e. is running or not).")
            ->setHelp($this->getHelpFromFile("status"))
        ;
    }

    /**
     * Executes status command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->forceRootPermissions($output);

        // Show message based on Dej status
        switch (self::getStatus()) {
            case self::STATUS_STOPPED:
                $output->writeln("Not running.");
                break;
            
            case self::STATUS_PARTIAL:
                $output->warn("Partially running.");
                break;
            
            case self::STATUS_RUNNING:
                $output->writeln("Running!");
                break;

            default:
                $output->warn("Too many running instances.");
                break;
        }           
    }

    /**
     * Returns running screens by their names.
     *
     * @return array Array of screen name which are running.
     */
    protected static function getRunningScreens(): array
    {
        // Wipes all dead screens
        `screen -wipe`;

        // Lists screens
        $screens = `screen -ls`;

        // Search for Dej screens and convert them to lowercase
        preg_match_all("/[0-9a-z]*\.dej/i", $screens, $matches, PREG_PATTERN_ORDER);
        array_walk($matches[0], function (&$val) {
            $val = strtolower(str_replace(".dej", "", $val));
        });

        return $matches[0];
    }

    /**
     * Returns the number of running processes.
     *
     * @return int The number of running processes of Dej.
     */
    public static function getRunningScreensCount(): int
    {
        return count(self::getRunningScreens());
    }

    /**
     * Returns the status of Dej.
     *
     * @return void One of the STATUS_* constants.
     */
    public static function getStatus(): int
    {
        $screensCount = self::getRunningScreensCount();

        if ($screensCount === 0)
            return self::STATUS_STOPPED;

        if ($screensCount > 0 && $screensCount < self::SCREEN_NUMBER)
            return self::STATUS_PARTIAL;

        if ($screensCount === self::SCREEN_NUMBER)          
            return self::STATUS_RUNNING;

        if ($screensCount > self::SCREEN_NUMBER)
            return self::STATUS_OVERFLOW;
    }

    /**
     * Tells whether a process is running or not.
     *
     * @param string $process The process name to be checked.
     * @return bool
     */
    public static function isRunning(string $process): bool
    {
        return in_array(strtolower($process), self::getRunningScreens());
    }
}
