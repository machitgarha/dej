<?php

// Includes
$incPath = "includes";
$filesPath = [
    "json.php",
    "shell.php",
    "data_validation.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

echol("Loading configuration file...");

// Load configuration file
$dataJson = new JSON();
$dataJson->load_file("data.json", false, true,
    "Help: If it doesn't exist, create it by running 'dej config create'.");

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