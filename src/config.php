<?php

// Break if incorrect number of arguments supplied
if ($argc !== 3)
    throw new InvalidArgumentException();

// Includes
$incPath = "includes";
$filesPath = [
    "load.php",
    "shell.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echol("Loading configuration file...");

// Load configurations
$dataJson = new LoadJSON("data.json", LoadJSON::OBJECT_DATA_TYPE, true);
$dataJson->type_validation();
$configData = $dataJson->data;

// Check if configuration file exists, and if not, create it
if ($dataJson->data)
    echol("Loaded successfully.", 2);
else {
    echol("File doesn't exist, creating it...");
    touch($dataJson->prefix . "/data.json");
    echol("Created.", 2);
}

// Load all possible options
$typeJson = new LoadJSON("data/validation/type.json",
    LoadJSON::OBJECT_DATA_TYPE, false, false);
$types = $typeJson->data->{"data.json"};

// Extract all possible options
$possibleOptions = [];
foreach ($types as $fields)
    foreach ($fields as $field)
        if (!is_array($field))
            array_push($possibleOptions, $field);
        else
            array_push($possibleOptions, $field[0]);

// Set arguments
$option = $argv[1];
$value = $argv[2];

// Break if it is an invalid option
if (!in_array($option, $possibleOptions))
    exit("There is no $option option exists."
        . "Check 'dej --help config list' for more information." . PHP_EOL);

// Check if there is any field exist
if ($dataJson->get_field($option))
    echol("Current value: '" . $dataJson->get_field($option, null, true) . "'");

echol("Changing it to '$value'...");

// Change field's value
$dataJson->add_field([
    $option,
    $value
]);

echol("Changed!", 2);
echol("Saving the change...");

// Open the file to save
$dataJsonFile = fopen($dataJson->filePath, "w");
fwrite($dataJsonFile, json_encode($dataJson->data, JSON_PRETTY_PRINT));
fclose($dataJsonFile);

echol("Saved!");