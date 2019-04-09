<?php
/**
 * Dej component file.
 *
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Component;

use Webmozart\PathUtil\Path;
use MAChitgarha\Component\Pusheh;

/**
 * Holds path of files and directories to work with.
 */
class PathData
{
    /**
     * Creates and returns the configuration directory path, containing configuration files.
     *
     * @return string
     */
    public static function createAndGetConfigDirPath(): string
    {
        return self::createDirAndReturn(Path::join(getenv("HOME"), ".config/dej"));
    }

    /**
     * Return paths of configuration files used by Dej.
     *
     * @return array An array containing:
     * 'config': The configuration file path for basic and general configurations,
     * 'users': The configuration file path for users data.
     */
    public static function getConfigFilesPaths(): array
    {
        $configDir = self::createAndGetConfigDirPath();

        return [
            "config" => Path::join($configDir, "config.json"),
            "users" => Path::join($configDir, "users.json"),
        ];
    }

    /**
     * Returns the path to stopper, that causes the sniffer to stop.
     *
     * The stopper file is a simple file to send stop signal to the sniffer. The reason of basically
     * not killing the sniffer process is that it should ends the last working file; otherwise, it
     * might cause corrupted files or emptied ones.
     * @return string
     */
    public static function getStopperFilePath(): string
    {
        return Path::join(self::createAndGetConfigDirPath(), "stopper");
    }

    /**
     * Creates and returns path to where Tcpdump raw packets data will be saved.
     * 
     * @return string
     */
    public static function createAndGetTcpdumpDataDirPath(): string
    {
        return self::createDirAndReturn("/tmp/dej/tcpdump");
    }

    /**
     * Creates a directory and returns its path.
     *
     * @param string $dirPath Directory path to be created and returned.
     * @return string
     */
    private static function createDirAndReturn(string $dirPath): string
    {
        Pusheh::createDirRecursive($dirPath);
        return $dirPath;
    }
}