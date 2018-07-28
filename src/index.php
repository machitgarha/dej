<?php

// Check for root permissions
if (`which whoami` === null)
    echo "Warning: We cannot detect if you gave root permissions or " .
        "not. \nBe sure you did that, otherwise, capturing won't " .
        "perform." . PHP_EOL;
elseif (trim(`whoami`) !== "root")
    exit("Root permissions needed." . PHP_EOL);

// Includes
$incPath = "includes";
$filesPath = [
    "load.php",
    "directory.php",
    "compare_files.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

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
    `$screen -d -m $logPart $php -f $sourceDir/$fname.php`;
}
