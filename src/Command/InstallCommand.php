<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Webmozart\PathUtil\Path;
use Dej\Component\ShellOutput;
use Dej\Exception\OutputException;
use Dej\Exception\InternalException;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->setDescription("Installs Dej (or updates it).")
        ;
    }

    /**
     * @throws InternalException When installation path cannot be detected.
     * @throws OutputException When try installing in a repository environment.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $this->forceRootPermissions($output);

        $output->writeln("Preparing...");

        // Get options
        $forceMode = $input->getOption("force");

        // Extract $PATH paths
        $sysInstallationDirs = explode(":", getenv("PATH"));
        if (empty($sysInstallationDirs))
            throw new InternalException("Cannot find an installation path.");

        $installationDir = $sysInstallationDirs[0];
        $defaultInstallationDir = "/usr/local/bin";
        if (in_array($defaultInstallationDir, $sysInstallationDirs))
            $installationDir = $defaultInstallationDir;

        $installationPath = Path::join($installationDir, "dej");
        $currentPharPath = \Phar::running(false);

        // Install if it's not installed or force mode is enabled
        if (!file_exists($installationPath) || $forceMode)
            // Check if user is working with a Phar or with the repository
            if (!empty($currentPharPath))
                copy($currentPharPath, $installationPath);
            else
                throw new OutputException("You must install Dej as a Phar file.");
        else
            throw new OutputException("Already installed.");

        // Grant right permissions
        chmod($installationPath, 0755);

        $output->writeln([
            "Completed.",
            "Try 'dej help' for more information."
        ]);
    }
}
