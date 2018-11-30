<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Loading configuration file...");

// Load configuration file and also validator
try {
    $dataJson = new JSONFile("data.json", "config");
} catch (Throwable $e) {
    $sh->error($e);
}

$sh->echo("Loaded successfully.", 2);

// Check for missing fields
$sh->echo("Checking for missing important fields...");

$validated = (new DataValidation($dataJson))->classValidation();
if (empty($validated->getWarnings(true)))
    $sh->echo("All important fields have been set!");
$validated->output(true);

// Check for bad field values (e.g. bad MAC address for interface.mac)
$sh->echo("Checking for invalid field values...", 1, 1);

$validated = (new DataValidation($dataJson))->typeValidation();
if (empty($validated->getWarnings(true)))
    $sh->echo("Looks good!");
$validated->output(true);
