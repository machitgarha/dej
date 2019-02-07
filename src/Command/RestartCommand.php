<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;


class RestartCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName("restart");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Restarting Dej...");

        $this->checkRootPermissions();

        // Restart when permissions granted
        $dej = $this->getApplication();

        try {
            $args = new ArrayInput([]);
            $nullOutput = new NullOutput();
            $stopResult = $dej->find("stop")->run($args, $nullOutput);
            $startResult = $dej->find("start")->run($args, $nullOutput);
        } catch (\Throwable $e) {}

        if ($stopResult !== 0 || $startResult !== 0)
            throw new \Exception("Cannot restart Dej");

        $output->writeln("Done!");
    }
}
