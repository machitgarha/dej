<?php

// Break if incorrect number of arguments supplied
if ($argc !== 3)
    throw new InvalidArgumentException();

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Loading configuration file...");

// Load configurations
$loaded = true;
try {
    $dataJson = new JSONFile("data.json", "config");
} catch (Throwable $e) {
    $sh->error($e);
}

// Check if configuration file exists, and if not, create it
if ($loaded)
    $sh->echo("Loaded successfully.", 2);
else {
    $sh->echo("The file couldn't be loaded.");
    require "src/create.php";
    $sh->echo();
}

// Load all possible options
try {
    $typeJson = new JSONFile("type.json", "data/validation");
    $types = $typeJson->data->{"data.json"};
} catch (Throwable $e) {
    $sh->error($e);
}

// Extract all possible options
$possibleOptions = [];
foreach ($types as $fieldName => $fieldData)
    array_push($possibleOptions, $fieldName);

// Set arguments
$option = $argv[1];
$value = $argv[2];

// Break if it is an invalid option
if (!in_array($option, $possibleOptions)) {
    $sh->echo("There is no $option option exists.");
    $sh->exit("Check 'dej config list' for more information.");
}

// Get field's current value
$currentValue = $dataJson->get($option);

// Fix values
$fieldType = $types->$option->type ?? "string";
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
        $json = new JSON([$option => $value]);
        $json->filename = "data.json";
        DataValidation::type_validation($json, true);
        break;
}

// Check if there is any field exist
if ($currentValue !== null)
    $sh->echo("Current value is " . json_encode($currentValue) . ".");

// Check if values are equal, then break if it is
if ($currentValue === $value) {
    $sh->echo("Nothing to do!");
    goto check;
}

$sh->echo("Setting $option to " . json_encode($value) . "...");

// Change field's value
$dataJson->set($option, $value);

$sh->echo("Set!", 2);
$sh->echo("Saving...");

// Open the file to save
try {
    $dataJson->save();
} catch (Throwable $e) {
    $sh->error($e);
}

$sh->echo("Saved!", 2);

// Restart Dej to see the effects and show the result
ob_start();
require "src/restart.php";
$restartOutput = ob_get_clean();
if (preg_match("/(Everything got running!)/", $restartOutput))
    $sh->echo("Restarted successfully!");
else
    $sh->echo("Failed. Run 'dej restart' for more information.");

check:
// Check for warnings
ob_start();
try {
    $dataJson = new JSONFile("data.json", "config");
} catch (Throwable $e) {
    $sh->warn($e);
}
DataValidation::class_validation($dataJson, true);
DataValidation::type_validation($dataJson);
$warningsOutput = ob_get_clean();

// If at least a warning found, print it
$warningsCount = preg_match_all("/(warning:)/i", $warningsOutput);
if (!empty($warningsOutput)) {
    $sh->echo("Found $warningsCount warning(s) in the configuration file.", 1, 1);
    $sh->echo("Try 'dej config check' for more details.");
}