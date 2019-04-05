<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use Dej\Component\ShellOutput;
use Dej\Component\JSONFileValidation;

$shellOutput = new ShellOutput();

// Load configurations and validate it
try {
    $config = (new JSONFileValidation("config/data.json"))
        ->checkEverything()
        ->throwFirstError();
} catch (Throwable $e) {
    return $shellOutput->error($e->getMessage());
}

// Create and change directory to the path of saved files
$dirPath = $config->get("save_to.path");
Pusheh::createDirRecursive($dirPath);
chdir($dirPath);

// Set required variables from data file
$backupDirName = $config->get("backup.dir");
$backupTimeout = $config->get("backup.timeout");

// Create backup directory (if needed)
Pusheh::createDirRecursive($backupDirName);

while (true) {
    // Make a list of the whole files
    $filesDir = new DirectoryIterator(".");

    // Create the timestamp file (to see when backup was made)
    try {
        $now = (new DateTime(`date`))->format("Y-m-d H:i:s");
    } catch (Throwable $e) {
        $now = time();
    }
    file_put_contents(Path::join($backupDirName, "update_time"), $now);

    // Make backup from the files
    foreach ($filesDir as $file)
        if ($file->isFile()) {
            $filename = $file->getFilename();
            `cp $filename $backupDirName/$filename`;
        }

    // Timeout
    sleep($backupTimeout);
}
