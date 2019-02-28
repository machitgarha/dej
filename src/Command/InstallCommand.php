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
use Symfony\Component\Console\Input\InputOption;
use Webmozart\PathUtil\Path;
use Symfony\Component\Process\Process;
use Dej\Component\ShellOutput;

/**
 * Installs Dej.
 */
class InstallCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("install")
            ->addOption("force", null, InputOption::VALUE_NONE)
            ->addOption("update", null, InputOption::VALUE_NONE)
            ->setDescription("Installs Dej (or updates it).")
        ;
    }

    /**
     * Executes install command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     * @throws \Exception When installation path cannot be detected.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->forceRootPermissions($output);

        $output->writeln("Preparing...");

        // Get options
        $forceMode = $input->getOption("force");
        $updateMode = $input->getOption("update");

        // Data path
        $dataPath = Path::join(__DIR__, "../..");

        // Extract $PATH info and set installation path
        $defaultInstallPath = "/usr/local/bin";
        $paths = explode(":", $_SERVER["PATH"]);
        // Break if install path cannot be specified
        if (empty($paths))
            throw new \Exception("Unknown installation path.");
        $installPath = $paths[0];
        if (in_array($defaultInstallPath, $paths))
            $installPath = $defaultInstallPath;

        // Edit the source line of the Dej file to match with the current path
        $dejFile = new \SplFileObject(__DIR__ . "/../../dej", "r");
        $dejFileContentLines = explode(PHP_EOL, $dejFile->fread($dejFile->getSize()));
        foreach ($dejFileContentLines as $key => $line)
            if ($line === "# SOURCE") {
                $dejFileContentLines[$key + 1] = "src=\"$dataPath\"";
                break;
            }
        
        $output->writeln($updateMode ? "Updating..." : "Installing...");

        // Update repository automatically
        if ($updateMode) {
            $updateCommand = new Process(["git", "pull"]);
            $updateCommand->run();

            // Check if successfully updated or not
            $isUpdatedCommand = new Process(["git", "pull"]);
            $isUpdatedCommand->run();
            if (trim($isUpdatedCommand->getOutput()) !== "Already up to date.")
                $output->error("Cannot update local repository. Aborting.");
        }

        // Create a temporary command file matching new changes
        $tmpFile = "dej" . time() . ".tmp";
        $newFileContents = implode(PHP_EOL, $dejFileContentLines);
        $dejTmpFile = new \SplFileObject($tmpFile, "w");
        $dejTmpFile->fwrite($newFileContents);

        // The temporary file path to install
        $dej = Path::join($installPath, "dej");
        
        // Prevent from overwriting an older version
        $toInstall = !file_exists($dej) || $forceMode || $updateMode;

        // Move the temporary file, if not installed or force mode is enabled
        if ($toInstall)
            copy($tmpFile, $dej);
        unlink($tmpFile);

        if (!$toInstall)
            $output->error("Already installed.");

        // Grant right permissions
        chmod($dej, 0755);

        $output->writeln("Completed.");
        if (!$updateMode)
            $output->writeln("Try 'dej help' for more information.");
    }
}
