<?php

namespace Dej\Command;

use Webmozart\PathUtil\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class StopCommand extends BaseCommand
{
    protected $rootPermissions;

    protected function configure()
    {
        $this->setName("stop");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Stopping Dej...");

        $this->checkRootPermissions();

        // Check if there are some screens to stop
        if (StatusCommand::getStatus() === StatusCommand::STATUS_STOPPED) {
            $output->writeln("Not running.");
            exit();
        }
    
        // Stop TCPDump and the reader instances
        `screen -X -S Tcpdump.dej quit`;
        `screen -X -S Reader.dej quit`;

        // Send signal to stop sniffer, and wait for the process to end
        if (StatusCommand::isRunning("sniffer")) {
            $stopFile = "config/stop"; 
            touch($stopFile);
            while (file_exists($stopFile))
                usleep(100 * 1000);
        }

        // Stop the backup process
        `screen -X -S Backup.dej quit`;        

        $output->writeln("Done!");
    }
}
