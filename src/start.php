<?php

// Include all include files
require_once "./includes/autoload.php";

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
$dataJson = require_json_file("data.json", "config");

// Data validation
DataValidation::class_validation($dataJson);
DataValidation::type_validation($dataJson);

// Save validated data for future usages
$config = $dataJson->data;

// Perform comparison between files and backup files
$path = $config->save_to->path;
$backupDir = $config->backup->dir;
compare_files($path, $backupDir);

// Load executables
$php = $argv[1];
$screen = $config->executables->screen;
$tcpdump = $config->executables->tcpdump;

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
    $logPart = $config->logs->screen ? "-L -Logfile $logDir/$fname.log": "";
    `$screen -S dej -d -m $logPart $php -f $sourceDir/$fname.php`;
}

echol("Everything got running!");
