<?php

// Includes
$incPath = "includes";
$filesPath = [
    "load_json.php",
    "directory.php",
    "compare_files.php",
    "root_permissions.php",
    "screen.php",
    "shell.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

echol("Starting Dej...");

// Stop if root permissions not granted
if (!root_permissions())
    return;

// If there are some screens running, prompt user
if (count(search_screens()) > 0) {
    // Prompt user to stop started screens or not
    echo "Already running. Stop? [Y(es)/n(o)/c(ancel)] ";
    $cliInput = fopen("php://stdin", "r");
    // Analyze user input
    $response = strtolower(trim(fgetc($cliInput)));
    fclose($cliInput);

    // If user wants to cancel, cancel!
    if ($response === "c")
        exitl("Canceled!");

    // Check if user wanted to stop or not, if yes, continue
    if ($response !== "n")
        echol(`php -f src/stop.php` . "Starting Dej...");
}

// Load configurations
$dataJson = new LoadJSON("data.json", LoadJSON::OBJECT_DATA_TYPE, false, true,
    "Help: If it doesn't exist, create it by running 'dej config create'.");
$dataJson->class_validation();
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
        exitl("You must have {$neededExecutable[0]} command installed," .
            " i.e., the specified executable file cannot be used (" .
            "{$neededExecutable[1]}). Fix it by editing executables " .
            "field in config/data.json.");

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

echol("Everything got running!");
