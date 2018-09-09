<?php

// Includes
$incPath = "includes";
$filesPath = [
    "directory.php",
    "shell.php",
    "root_permissions.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

echol("Preparing configuration file...");

should_not_be_root();

// Create directory if it does not exist
$configDir = "config";
if (directory($configDir))
    echol("Created $configDir directory.");

$configFilePath = "$configDir/data.json";

if (file_exists($configFilePath))
    exitl("Configuration file exists.");

// Create configuration file, if it does not exist
if (touch($configFilePath))
    echol("Created $configFilePath successfully.");
else
    exitl("Cannot create configuration file ($configFilePath).");
