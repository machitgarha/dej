<?php

// Include all include files
require_once "./includes/autoload.php";
$sh = new Shell();

$sh->echo("Loading configuration file...");

// Load configuration file
try {
    $dataJson = new JSONFile("data.json", "config");
} catch (Throwable $t) {
    $sh->exit("internal_error", []);
}

$sh->echo("Loaded successfully.", 2);

// Check for missing fields
$sh->echo("Checking for missing important fields...");
ob_start();
DataValidation::class_validation($dataJson, true);
$missingFieldOutput = ob_get_clean();

// Output missing fields, if exist
if (empty($missingFieldOutput))
    $sh->echo("All important fields have been set!", 2);
else
    $sh->echo($missingFieldOutput);

// Check for bad field values (e.g. bad MAC address for interface.mac)
$sh->echo("Checking for bad field values...");
ob_start();
DataValidation::type_validation($dataJson);
$badFieldsOutput = ob_get_clean();

// Output missing fields, if exist
if (empty($badFieldsOutput))
    $sh->echo("Nothing bad; looks good!");
else
    echo $badFieldsOutput;