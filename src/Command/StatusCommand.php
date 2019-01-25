<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends RootCommand
{
    private const SCREEN_NUMBER = 4;
    public const STATUS_STOPPED = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_PARTIAL = 2;

    protected function configure()
    {
        $this->setName("status");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Search for Dej screens
        $screensCount = $this->getRunningScreensCount();
        if ($screensCount === 0)
            $this->sh->exit("Not running.");
        if ($screensCount > 0 && $screensCount < self::SCREEN_NUMBER)
            $this->sh->warn("Partially running.");
        if ($screensCount === self::SCREEN_NUMBER)            
            $this->sh->exit("Running!");
    }

    public static function getRunningScreens(): array
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

    public static function isRunning(): bool
    {
        return self::getRunningScreensCount() === self::SCREEN_NUMBER;
    }
}