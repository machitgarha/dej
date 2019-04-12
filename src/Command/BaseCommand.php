<?php
/**
 * Dej command files.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Dej\Component\JSONFileValidation;
use Dej\Component\PathData;
use Dej\Exception\OutputException;

/**
 * The base class for all Dej commands.
 */
abstract class BaseCommand extends Command
{
    /**
     * Loads a JSON file.
     *
     * @param string $whichConfigFile The filename to load, without '.json' suffix.
     * @return JSONFileValidation
     */
    protected function loadConfig(string $whichConfigFile): JSONFileValidation
    {
        PathData::createAndGetConfigDirPath();

        return new JSONFileValidation($whichConfigFile);
    }

    /**
     * Forces user to grant root permissions.
     *
     * @param OutputInterface $output
     * @return void
     * @throws OutputException When root permission has not been granted.
     */
    protected function forceRootPermissions(OutputInterface $output): void
    {
        // Cannot detect
        $cannotDetectMessage = "We cannot detect if root permissions is granted or not. Please " .
            "make sure you've granted, otherwise you may have problems.";
        if (!function_exists("posix_getuid")) {
            $output->writeln($cannotDetectMessage);
        }

        if (posix_getuid() !== 0) {
            throw new OutputException("Root permission is needed.");
        }
    }

    /**
     * Tells whether is root permission enabled or not.
     *
     * @param OutputInterface $output
     * @return bool
     */
    protected function areWeRoot(OutputInterface $output): bool
    {
        /*
         * The reason why we use the following method inside this method and not the opposite is
         * facing the condition that root permissions cannot be detected.
         */
        try {
            $this->forceRootPermissions($output);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Gets a help from a file.
     *
     * It tries to get the help file, but if reading it was unsuccessful, it uses the command's description. Also, you can inject values in the help file contents.
     *
     * @param string $filename The filename of the help, without the
     * @param array $data Data to be injected. Placeholder is in the form of "{{sth}}".
     * @return string
     */
    protected function getHelpFromFile(string $filename, array $data = []): string
    {
        $filePath = __DIR__ . "/../../data/helps/$filename.txt";

        try {
            $file = new \SplFileObject($filePath, "r");
            $contents = $file->fread($file->getSize());
            $file = null;
        } catch (\Throwable $e) {
            return $this->getDescription();
        }

        foreach ($data as $placeholder => $value) {
            $contents = str_replace("{{$placeholder}}", $value, $contents);
        }

        return $contents;
    }
}
