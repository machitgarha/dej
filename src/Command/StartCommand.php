<?php

namespace Dej\Command;

use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Dej\Element\DataValidation;
use Symfony\Component\Process\Process;
use MAChitgarha\Component\JSON;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\PhpExecutableFinder;
use Dej\Element\ContinuousProcess;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;

class StartCommand extends BaseCommand
{
    protected $phpExecutable = "php";
    protected $rootPermissions;

    protected function configure()
    {
        $this->setName("start");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting Dej...");

        $this->checkRootPermissions();

        // If there are some screens running, prompt user
        if (StatusCommand::getStatus() !== StatusCommand::STATUS_STOPPED) {
            // Prompt user to restart Dej or not
            $helper = $this->getHelper("question");
            $question = new ConfirmationQuestion("Already running. Restart? [N(o)/y(es)] ", false);
            if ($helper->ask($input, $output, $question)) {
                $output->writeln("Restarting...");
                try {
                    $result = $this->getApplication()->find("stop")
                        ->run(new ArrayInput([]), new NullOutput());
                } catch (\Throwable $e) {}
                if ($result !== 0)
                    throw new \Exception("Cannot stop Dej");
            } else {
                $output->writeln("Aborted.");
                exit();
            }
        }

        try {
            // Load configurations and validate it
            $config = DataValidation::new($this->loadConfiguration("data"))
                ->classValidation()
                ->typeValidation()
                ->return();
        } catch (\Throwable $e) {
            $this->sh->error($e);
        }

        // Perform comparison between files and backup files
        $path = $config->get("save_to.path");
        $backupDir = $config->get("backup.dir");
        $this->compareFiles($path, $backupDir);

        // Load executables
        $php = $this->phpExecutable;
        $screen = $config->get("executables.screen");
        $tcpdump = $config->get("executables.tcpdump");

        // Logs directory, and create it if not found
        $logsDir = $config->get("logs.path");
        Pusheh::createDirRecursive($logsDir);

        // Check for installed commands
        $neededExecutables = [
            ["screen", $screen],
            ["tcpdump", $tcpdump]
        ];
        foreach ($neededExecutables as $neededExecutable)
            if (empty(`which {$neededExecutable[1]}`))
                $sh->error("You must have {$neededExecutable[0]} command installed, i.e., the specified" .
                    "executable file cannot be used ({$neededExecutable[1]}). Fix it by editing " .
                    "executables field in config/data.json.");

        // Names of directories and files
        $sourceDir = "src/Process";
        $filenames = [
            "Tcpdump",
            "Reader",
            "Sniffer",
            "Backup",
        ];

        // Run each file with a logger
        foreach ($filenames as $filename) {
            // Check if logs were enabled for screen or not
            $logPath = Path::join($logsDir, $filename);
            $processFilePath = Path::join($sourceDir, "$filename.php");
            $logPart = $config->get("logs.screen") ? "-L -Logfile $logPath.log" : "";
            $cmd = "$screen -S $filename.dej -d -m $php $processFilePath";

            $process = Process::fromShellCommandline($cmd);
            $process->run();
        }

        $status = StatusCommand::getStatus();
        if ($status === StatusCommand::STATUS_RUNNING)
            $this->sh->echo("Done!");
        elseif ($status === StatusCommand::STATUS_PARTIAL || $status === StatusCommand::STATUS_STOPPED)
            $this->sh->error("Something went wrong. Try again!");
        else
            $this->sh->warn("Too much instances are running.");
    }

    // Replace a broken file with the backup
    private function compareFiles(string $path, string $backupDir)
    {
        // Remove colons from number
        $getNum = function (string $path) {
            return (int)str_replace(",", "", file_get_contents($path));
        };

        // Get files info
        Pusheh::createDirRecursive($path);
        $files = new \DirectoryIterator($path);

        // Add path to backup directory
        $backupDir = "$path/$backupDir";

        // Perform on all files
        foreach ($files as $file) {
            // Get names and paths
            $filename = $file->getFilename();
            $filePath = Path::join($path, $filename);
            $backupFilePath = Path::join($backupDir, $filename);

            // Check for a broken file, and replace it, if needed
            if (is_dir($backupDir) && file_exists($backupFilePath) &&
            $getNum($backupFilePath) > $getNum($filePath)) {
                // Remove the broken file
                unlink($filePath);

                // Replace it with the backup file
                copy($backupFilePath, $filePath);
            }
        }
    }
}
