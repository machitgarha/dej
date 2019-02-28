<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

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
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Dej\Element\ShellOutput;

/**
 * Starts Dej.
 */
class StartCommand extends BaseCommand
{
    /** @var string PHP executable path, located in ./data/php. */
    protected $phpExecutable;

    /**
     * Sets the PHP executable before starting Dej.
     *
     * @param string $name The command name, i.e. start.
     */
    public function __construct(string $name = null)
    {
        $this->phpExecutable = trim(file_get_contents(__DIR__ . "/../../data/php"));
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName("start")
            ->setDescription("Starts Dej.")
            ->setHelp($this->getHelpFromFile("start"))
        ;
    }

    /**
     * Executes start command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->forceRootPermissions($output);

        $output->writeln("Starting Dej...");

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

                // If something goes wrong with stopping Dej
                if (!isset($result) || $result !== 0)
                    throw new \Exception("Cannot stop Dej");
            // User canceled starting Dej
            } else {
                $output->writeln("Aborted.");
                return;
            }
        }

        // Load configurations and validate it
        $config = DataValidation::new($this->loadJson("data"))
            ->classValidation()
            ->typeValidation()
            ->return();

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

        // Checks for installed commands
        $neededExecutables = [
            ["screen", $screen],
            ["tcpdump", $tcpdump]
        ];
        foreach ($neededExecutables as $neededExecutable)
            if (empty(`which {$neededExecutable[1]}`))
                $output->error("You must have {$neededExecutable[0]} command installed, i.e., the"
                    . "specified executable file cannot be used ({$neededExecutable[1]}). Fix it by"
                    . " editing executables field in config/data.json.");

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
            $output->writeln("Done!");
        elseif ($status === StatusCommand::STATUS_PARTIAL || $status === StatusCommand::STATUS_STOPPED)
            $output->writeln("Something went wrong. Try again!");
        else
            $output->writeln("Too much instances are running.");
    }

    /**
     * Replaces a broken file with its backup.
     * 
     * A broken file is a file that is got empty or is smaller than its backup.
     *
     * @param string $path The path of the main files.
     * @param string $backupDir The path of the backup files.
     * @return int
     */
    private function compareFiles(string $path, string $backupDir): void
    {
        // Remove colons from number
        $getNum = function (string $path) {
            return (int)str_replace(",", "", file_get_contents($path));
        };

        // Get files information
        Pusheh::createDirRecursive($path);
        $files = new \DirectoryIterator($path);
        $backupDir = "$path/$backupDir";

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $filePath = Path::join($path, $filename);
            $backupFilePath = Path::join($backupDir, $filename);

            // Replacing broken file
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
