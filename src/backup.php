<?php

// Include all include files
require_once "./includes/autoload.php";
$sh = new Shell();

// Load configurations
try {
    $dataJson = new JSONFile("data.json", "config");
    $config = $dataJson->data;
} catch (Throwable $t) {
    $sh->exit("internal_error", []);
}

// Validate data
DataValidation::class_validation($dataJson);
DataValidation::type_validation($dataJson);

// Create (if needed) and change directory to the path of saved files
$dirPath = $config->save_to->path;
directory($dirPath);
chdir($dirPath);

// Set required variables from data file
$backupDirName = $config->backup->dir;
$backupTimeout = $config->backup->timeout;

// Create backup directory (if needed)
directory($backupDirName);
$backupDirName = force_end_slash($backupDirName);

while (true) {
    // Make a list of the whole files
    $filesDir = new DirectoryIterator(".");

    // Create the timestamp file (to see when backup was made)
    fwrite($f = fopen($backupDirName . "update_time", "w"), time());
    fclose($f);

    // Make backup from the files
    foreach ($filesDir as $file)
        if ($file->isFile()) {
            $filename = $file->getFilename();
            `cp $filename $backupDirName/$filename`;
        }

    // Timeout
    sleep($backupTimeout);
}
