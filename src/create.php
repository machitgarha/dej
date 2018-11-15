<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Preparing to create configuration file...");

should_not_be_root();

// Create directory if it does not exist
$configDir = "config";
if (directory($configDir))
    $sh->echo("Created $configDir directory.");

$configFilePath = "$configDir/data.json";

if (file_exists($configFilePath))
    $sh->error("Configuration file exists.");

// Create configuration file, if it does not exist
if (touch($configFilePath))
    $sh->echo("Created $configFilePath successfully.");
else
    $sh->error("Cannot create configuration file ($configFilePath).");
