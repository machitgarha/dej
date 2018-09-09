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
    require "$incPath/$filePath";

echol("Loading configuration file...");

// Load configurations
$dataJson = new LoadJSON("data.json", LoadJSON::OBJECT_DATA_TYPE, true);
$dataJson->class_validation();
$configData = $dataJson->data;

// Check if configuration file exists, and if not, create it
if ($dataJson->data)
    echol("Loaded successfully.", 2);
else {
    echol("File doesn't exist, creating it...");
    directory($dataJson->prefix);
    touch($dataJson->filePath);
    echol("Created.", 2);
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
$currentValue = $dataJson->get_field($option, null, true);

// Check if there is any field exist
if ($currentValue !== null)
    echol("Current value is " . json_encode($currentValue) . ".");

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
}

// Check if values are equal, then break if it is
if ($currentValue === $value) {
    echol("Nothing to do!", 2);
    goto check;
}

echol("Setting it to " . json_encode($value) . "...");

// Change field's value
$dataJson->add_field($option, $value);

echol("Set!", 2);
echol("Saving the change...");

// Open the file to save
$dataJsonFile = @fopen($dataJson->filePath, "w");

// Warn user if cannot save
if (!$dataJsonFile)
    exitl("Error: Cannot open the {$dataJson->filePath} for saving.");

// Write new data to the file with a pretty format
fwrite($dataJsonFile, json_encode($dataJson->data, JSON_PRETTY_PRINT));
fclose($dataJsonFile);

echol("Saved!", 2);

// Restart Dej to see the effects
echol(`./dej restart`);

check:
echol("Checking configuration file...");

// Check for missing fields, and output warnings for them
$dataJson = new LoadJSON("data.json");
$dataJson->class_validation(true);
$dataJson->type_validation();