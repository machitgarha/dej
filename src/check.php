<?php


// Includes
$incPath = "includes";
$filesPath = [
    "load_json.php",
    "shell.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

echol("Loading configuration file...");

// Load configuration file
$dataJson = new LoadJSON("data.json", LoadJSON::OBJECT_DATA_TYPE, false, true,
    "Help: If it doesn't exist, create it by running 'dej config create'.");

echol("Loaded successfully.", 2);

// Check for missing fields
echol("Checking for missing important fields...");
ob_start();
$dataJson->class_validation(true);
$missingFieldOutput = ob_get_clean();

// Output missing fields, if exist
if (empty($missingFieldOutput))
    echol("All important fields have been set!");
else
    echol($missingFieldOutput);

// Check for bad field values (e.g. bad MAC address for interface.mac)
echol("Checking for bad field values...");
ob_start();
$dataJson->type_validation();
$badFieldsOutput = ob_get_clean();

// Output missing fields, if exist
if (empty($badFieldsOutput))
    echol("Nothing bad; looks good!");
else
    echo $badFieldsOutput;