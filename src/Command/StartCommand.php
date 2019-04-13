<?php
/**
 * Dej command files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;
use Dej\Component\ShellOutput;
use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use Dej\Exception\OutputException;
use Symfony\Component\Console\Output\OutputInterface;
use Dej\Component\PathData;
use Dej\Exception\InternalException;

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
     * @param string|null $name The command name, i.e. start.
     */
    public function __construct(string $name = null)
    {
        $this->phpExecutable = PHP_BINARY;

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
     * @throws OutputException If Dej cannot be restarted.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

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
                } catch (\Throwable $e) {
                }

                // If something goes wrong with stopping Dej
                if (!isset($result) || $result !== 0) {
                    throw new OutputException("Cannot stop Dej.");
                }
            } else {
                return $output->abort("Aborted.");
            }
        }

        // Load configurations and validate it
        $config = $this->loadConfig("config")
            ->checkEverything()
            ->throwFirstError();

        // Perform comparison between files and backup files
        $path = $config->get("save_to.path");
        $backupDir = $config->get("backup.dir");

        $this->compareFiles($path, $backupDir);

        // Checks for installed commands
        $screen = $config->get("executables.screen");
        $tcpdump = $config->get("executables.tcpdump");

        foreach (["screen", "tcpdump"] as $exeName) {
            if (@empty(`which {$$exeName}`)) {
                throw new OutputException("You must have $exeName command installed, "
                    . "i.e. the specified executable file cannot be used ({$$exeName}). "
                    . "Change it by 'dej config'.");
            }
        }

        /*
         * To use files inside the installed Phar, unfortunately, due to a bug in Phar, we must
         * copy the Phar file to a new destination (i.e. data directory) to use its files. The bug
         * is that a Phar cannot be accessed via phar:// if its filename does not end with .phar!
         */
        if (!empty($currentPharPath = \Phar::running(false))) {
            $dejPharPath = Path::join(PathData::createAndGetDataDirPath(), "dej.phar");
            if (!@copy($currentPharPath, $dejPharPath))
                throw new InternalException("Cannot copy Dej installation file.");
        }

        // Get logging configurations
        $isLoggingEnabled = $config->get("logs.screen");
        $logsPath = $config->get("logs.path");

        // Try to create logs path, and when failed, alert user
        try {
            if ($isLoggingEnabled) {
                Pusheh::createDirRecursive($logsPath);
            }
        } catch (\Throwable $e) {
            throw new OutputException(
                "Cannot make logs directory path ($logsPath). " .
                "Change it using 'dej config logs.path *path*'."
            );
        }

        // Run each file with a logger
        foreach ($this->getProcessFilesInfo() as [$processName, $processFilePath]) {
            // Logging part, whether to log processes output or not
            $logFilePath = Path::join($logsPath, "$processName.log");
            $logPart = $isLoggingEnabled ? (new Process([
                "-L",
                "-Logfile",
                $logFilePath
            ]))->getCommandLine() : "";

            // Create the command to be executed in a screen
            $primaryProcessCommand = (new Process([
                $this->phpExecutable,
                $processFilePath,
            ]))->getCommandLine();
            
            // Run the process
            $command = "$screen -S $processName.dej $logPart -d -m $primaryProcessCommand";
            Process::fromShellCommandline($command)->run();
        }

        // Wait for processes to start
        usleep(200 * 1000);

        $status = StatusCommand::getStatus();
        if ($status === StatusCommand::STATUS_RUNNING) {
            $output->writeln("Done!");
        } else {
            // Stop all processes if something went wrong with any process
            $output->disableOutput();
            $this->getApplication()->find("stop")->run(new ArrayInput([]), $output);
            $output->enableOutput();

            throw new OutputException("Something went wrong!");
        }
    }

    /**
     * Replaces a broken file with its backup.
     *
     * A broken file is a file that is got empty or is smaller than its backup.
     *
     * @param string $path The path of the main files.
     * @param string $backupDir The path of the backup files.
     * @return void
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
        $backupDir = Path::join($path, $backupDir);

        foreach ($files as $file) {
            $processName = $file->getFilename();
            $filePath = Path::join($path, $processName);
            $backupFilePath = Path::join($backupDir, $processName);

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

    /**
     * Get process files information.
     *
     * Copy process files to Dej data directory and return the process information.
     *
     * @return \Generator Each process file information:
     * [0]: The process name (i.e. without extension),
     * [1]: The filename of the copied process file.
     */
    private function getProcessFilesInfo(): \Generator
    {
        $processNames = [
            "Tcpdump",
            "Reader",
            "Sniffer",
            "Backup",
        ];

        // Set and create destination and source directories
        $destDirPath = Path::join(PathData::createAndGetDataDirPath(), "processes");
        $srcDirPath = Path::join(__DIR__, "../Process");
        Pusheh::createDir($destDirPath);

        foreach ($processNames as $processName) {
            $destFilePath = Path::join($destDirPath, "$processName.php");
            $srcFilePath = Path::join($srcDirPath, "$processName.php");

            // Copy files if destination does not exist or source is newer
            if (!file_exists($destFilePath) || sha1_file($srcFilePath) !== sha1_file($destFilePath)) {
                if (!@copy($srcFilePath, $destFilePath)) {
                    throw new InternalException("Cannot copy files.");
                }
            }

            yield [
                $processName,
                $destFilePath
            ];
        }
    }
}
