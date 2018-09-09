<?php

// Break if incorrect number of arguments supplied
if ($argc !== 3)
    throw new InvalidArgumentException();

// Includes
$incPath = "includes";
$filesPath = [
    "load_json.php",
    "shell.php",
    "directory.php"
];
foreach ($filesPath as $filePath)
    require_once "$incPath/$filePath";

echol("Loading configuration file...");

// Load configurations
$dataJson = new LoadJSON("data.json", LoadJSON::OBJECT_DATA_TYPE, true);
$configData = $dataJson->data;

// Check if configuration file exists, and if not, create it
if ($dataJson->data)
    echol("Loaded successfully.", 2);
else {
    echol("It doesn't exist.");
    require "src/create.php";
    echol();
}

// Load all possible options
$typeJson = new LoadJSON("data/validation/type.json",
    LoadJSON::OBJECT_DATA_TYPE, false, false);
$types = $typeJson->data->{"data.json"};

// Extract all possible options
$possibleOptions = [];
foreach ($types as $fieldName => $fieldData)
    array_push($possibleOptions, $fieldName);

// Set arguments
$option = $argv[1];
$value = $argv[2];

// Break if it is an invalid option
if (!in_array($option, $possibleOptions)) {
    echol("There is no $option option exists.");
    exitl("Check 'dej config list' for more information.");
}

// Get field's current value
$currentValue = LoadJSON::get_field($option, $dataJson->data, true);

// Fix values
$fieldType = $types->$option->type ?? "string";
$arr = null;
switch ($fieldType) {
    case "bool":
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        break;

    case "int":
        $value = filter_var($value, FILTER_VALIDATE_INT);
        break;

    case "alphanumeric":
        $value = preg_replace("/[^a-z0-9]/i", "", $value);
        break;
    
    case "mac":
        LoadJSON::add_field($option, $value, $arr);
        $dataJson->type_validation($arr, true, true);
        break;
}

// Check if there is any field exist
if ($currentValue !== null)
    echol("Current value is " . json_encode($currentValue) . ".");

// Check if values are equal, then break if it is
if ($currentValue === $value) {
    echol("Nothing to do!");
    goto check;
}

echol("Setting $option to " . json_encode($value) . "...");

// Change field's value
LoadJSON::add_field($option, $value, $dataJson->data);

echol("Set!", 2);
echol("Saving...");

// Open the file to save
$dataJsonFile = @fopen($dataJson->filePath, "w");

// Warn user if cannot save
if (!$dataJsonFile)
    exitl("Error: Cannot open the {$dataJson->filePath} for saving.");

// Write new data to the file with a pretty format
fwrite($dataJsonFile, json_encode($dataJson->data, JSON_PRETTY_PRINT));
fclose($dataJsonFile);

echol("Saved!", 2);

echol("Restarting Dej...");

// Restart Dej to see the effects and show the result
ob_start();
require "src/restart.php";
$restartOutput = ob_get_clean();
if (preg_match("/(Everything got running!)/", $restartOutput))
    echol("Restarted successfully!");
else
    echol("Failed. Run 'dej restart' for more information.");

check:
// Check for warnings
ob_start();
$dataJson = new LoadJSON("data.json");
$dataJson->class_validation(true);
$dataJson->type_validation();
$warningsOutput = ob_get_clean();

// If at least a warning found, print it
$warningsCount = preg_match_all("/(warning:)/i", $warningsOutput);
if (!empty($warningsOutput)) {
    echol();
    echol("Found $warningsCount warning(s) in the configuration file.");
    echol("Try 'dej config check' for more details.");
}