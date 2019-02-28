<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class StatusCommand extends BaseCommand
{
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkRootPermissions();

        // Search for Dej screens
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
                $output->warn("Too many running instances");
                break;
        }           
    }

    protected static function getRunningScreens(): array
    {
        // Wipes all dead screens
        `screen -wipe`;

        // Lists screens
        $screens = `screen -ls`;

        // Search for Dej screens
        preg_match_all("/[0-9a-z]*\.dej/i", $screens, $matches, PREG_PATTERN_ORDER);

        array_walk($matches[0], function (&$val) {
            $val = strtolower(str_replace(".dej", "", $val));
        });

        return $matches[0];
    }

    public static function getRunningScreensCount(): int
    {
        return count(self::getRunningScreens());
    }

    public static function getStatus()
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

    public static function isRunning(string $process)
    {
        return in_array(strtolower($process), self::getRunningScreens());
    }
}
