<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends RootCommand
{
    const SCREEN_NUMBER = 4;
    const STATUS_STOPPED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_PARTIAL = 2;
    const STATUS_OVERFLOW = 3;

    protected function configure()
    {
        $this->setName("status");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Search for Dej screens
        switch (self::getStatus()) {
            case self::STATUS_STOPPED:
                $this->sh->exit("Not running.");
                break;
            
            case self::STATUS_PARTIAL:
                $this->sh->warn("Partially running.");
                break;
            
            case self::STATUS_RUNNING:
                $this->sh->exit("Running!");
                break;

            default:
                $this->sh->warn("Too many running instances");
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
        preg_match_all("/[0-9]*\.dej/", $screens, $matches, PREG_PATTERN_ORDER);

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
}