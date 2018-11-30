<?php

// Include all include files
require_once "./includes/autoload.php";

// Break if incorrect number of arguments supplied
if ($argc !== 3)
    $sh->error();

$sh->echo("Preparing...");

// Load configurations
$loaded = true;
try {
    $dataJson = new JSONFile("data.json", "config");

// If file doesn't exist, attemp to create it
} catch (FileExistenceException $e) {
    // Create the configuration file
    `./dej config create`;

    // Load it
    try {
        $dataJson = new JSONFile("data.json", "config");
    } catch (Throwable $e) {
        $sh->error($e);
    }
}

catch (Throwable $e) {
    $sh->error($e);
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

$sh->echo("Done!", 2);

// Break if it is an invalid option
if (!in_array($option, $possibleOptions)) {
    $sh->echo("There is no '$option' option exists.");
    $sh->exit("Run 'dej config list' for more info.");
}

$sh->echo("Updating...");

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
        if (!preg_match("/^([\da-f]{2}:){5}([\da-f]{2})$/i", $value))
            $sh->error("Wrong MAC address was given.");
        break;
}

// Change field's value
$dataJson->set($option, $value);

// Open the file to save
try {
    $dataJson->save();
} catch (Throwable $e) {
    $sh->error($e);
}

$sh->echo("Done!");
if ($currentValue !== null && $currentValue !== $value)
    $sh->echo(json_encode($currentValue) . " -> " . json_encode($value));
$sh->echo();

// Restart Dej to see the effects and show the result
echo `./dej restart`;

// Check for warnings
try {
    $warnings = (new DataValidation(new JSONFile("data.json", "config")))
        ->classValidation()
        ->typeValidation()
        ->getWarnings();
} catch (Throwable $e) {
    $sh->error($e);
}

// If at least a warning found, print it
$warningsCount = count($warnings);
if ($warningsCount !== 0) {
    $sh->echo("Found $warningsCount warning(s) in the configuration file.", 1, 1);
    $sh->echo("Try 'dej config check' for more details.");
}