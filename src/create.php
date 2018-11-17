<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Creating...");

// Create directory if it does not exist
directory("config");
$dataJsonFile = "config/data.json";

if (file_exists($dataJsonFile))
    $sh->error("Configuration file exists.");

// Create the configuration
touch($dataJsonFile);

// Make right permissions
chmod($dataJsonFile, 0755);

$sh->echo("Done!");