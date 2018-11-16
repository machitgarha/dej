<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Loading configuration file...");

// Load configuration file
try {
    $dataJson = new JSONFile("data.json", "config");
} catch (Throwable $e) {
    $sh->error($e);
}

$sh->echo("Loaded successfully.", 2);

// Check for missing fields
$sh->echo("Checking for missing important fields...");

$foundMissingField = DataValidation::class_validation($dataJson, true);

// Output missing fields, if exist
if (!$foundMissingField)
    $sh->echo("All important fields have been set!");
$sh->echo();

// Check for bad field values (e.g. bad MAC address for interface.mac)
$sh->echo("Checking for invalid field values...");

$foundInvalidFields = DataValidation::type_validation($dataJson);

// Output missing fields, if exist
if (!$foundInvalidFields)
    $sh->echo("Looks good!");