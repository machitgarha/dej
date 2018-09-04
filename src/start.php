<?php

// Includes
$incPath = "includes";
$filesPath = [
    "load.php",
    "directory.php",
    "compare_files.php",
    "root_permissions.php",
    "screen.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echo "Starting Dej..." . PHP_EOL;

// If there are some screens running, prompt user
if (count(search_screens()) > 0) {
    // Prompt user to stop started screens or not
    echo "Already running. Stop? [Y/n] ";
    $cliInput = fopen("php://stdin", "r");
    // Analyze user input
    $response = strtolower(trim(fgetc($cliInput)));
    fclose($cliInput);

    // Check if user wanted to stop or not, if yes, continue
    if ($response !== "n")
        echo `php -f src/stop.php` . "Starting Dej..." . PHP_EOL;
}

// Load configurations
$dataJson = new LoadJSON("data.json");
$dataJson->type_validation();
$configData = $dataJson->data;

// Perform comparison between files and backup files
$path = $configData->save_to->path;
$backupDir = $configData->backup->dir;
compare_files($path, $backupDir);

// Load executables
$php = $argv[1];
$screen = $configData->executables->screen;
$tcpdump = $configData->executables->tcpdump;

// Check for installed commands
$neededExecutables = [
    ["screen", $screen],
    ["tcpdump", $tcpdump]
];
foreach ($neededExecutables as $neededExecutable)
    if (!`which {$neededExecutable[1]}`)
        exit("You must have {$neededExecutable[0]} command installed," .
            " i.e., the specified executable file cannot be used (" .
            "{$neededExecutable[1]}). Fix it by editing executables " .
            "field in config/data.json." . PHP_EOL);

// Names of directories and files
$sourceDir = "src";
$logDir = "log";
$filenames = [
    "sniffer",
    "backup"
];

// Run each file with a logger
foreach ($filenames as $fname) {
    // Check if logs were enabled for screen or not
    directory($logDir);
    $logPart = $configData->logs->screen ? "-L -Logfile $logDir/$fname.log": "";
    `$screen -S dej -d -m $logPart $php -f $sourceDir/$fname.php`;
}

echo "Everything got running!" . PHP_EOL;
