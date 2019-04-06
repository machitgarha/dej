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
use Dej\Component\ShellOutput;
use Dej\Exception\OutputException;

/**
 * Uninstalls Dej.
 */
class UninstallCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("uninstall")
            ->setDescription("Uninstalls Dej.")
        ;
    }

    /**
     * Executes uninstall command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, $output)
    {
        $this->forceRootPermissions($output);

        $output->writeln("Preparing to uninstall Dej...");

        // Find where Dej has been installed
        $installationPath = trim(`which dej`);
        if (empty($installationPath))
            throw new OutputException("Not installed yet.");

        // Get agreement
        $helper = $this->getHelper("question");
        $question = new ConfirmationQuestion("Are you sure? [N(o)/y(es)] ", false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln("Aborted.");
            return;
        }

        $output->writeln("Uninstalling...");

        // Grant right permissions to be able to remove it
        chmod($installationPath, 0755);

        // Remove the file
        unlink($installationPath);

        $output->writeln("Uninstalled successfully.");
    }
}
