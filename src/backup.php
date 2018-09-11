<?php

// Includes
$incPath = "includes";
$filesPath = [
    "directory.php",
    "json.php",
    "data_validation.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

// Load configurations
$dataJson = new JSON();
$dataJson->load_file("data.json");
$config = $dataJson->data;

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
    echo "Hello";
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
