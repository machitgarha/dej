<?php

// Include all include files
require_once "./includes/autoload.php";

echol("Loading configuration file...");

// Load configuration file
$dataJson = require_json_file("data.json", "config");

echol("Loaded successfully.", 2);

// Check for missing fields
echol("Checking for missing important fields...");
ob_start();
DataValidation::class_validation($dataJson, true);
$missingFieldOutput = ob_get_clean();

// Output missing fields, if exist
if (empty($missingFieldOutput))
    echol("All important fields have been set!", 2);
else
    echol($missingFieldOutput);

// Check for bad field values (e.g. bad MAC address for interface.mac)
echol("Checking for bad field values...");
ob_start();
DataValidation::type_validation($dataJson);
$badFieldsOutput = ob_get_clean();

// Output missing fields, if exist
if (empty($badFieldsOutput))
    echol("Nothing bad; looks good!");
else
    echo $badFieldsOutput;