<?php

// Break if incorrect number of arguments supplied
if ($argc !== 3)
    throw new InvalidArgumentException();

// Includes
$incPath = "includes";
$filesPath = [
    "load.php",
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echo "Loading configuration file...\n";

// Load configurations
$dataJson = new LoadJSON("data.json", LoadJSON::OBJECT_DATA_TYPE, true);
$dataJson->type_validation();
$configData = $dataJson->data;

// Check if configuration file exists, and if not, create it
if ($dataJson->data)
    echo "Loaded successfully.\n\n";
else {
    echo "File doesn't exist, creating it...\n";
    touch($dataJson->prefix . "/data.json");
    echo "Created.";
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
    exit("There is no $option option exists.\n"
        . "Check 'dej --help config list' for more information." . PHP_EOL);

// Check if there is any field exist
if ($dataJson->get_field($option))
    echo "Current value: '" . $dataJson->get_field($option, null, true) . "'\n";

echo "Changing it to '$value'...\n";

// Change field's value
$dataJson->add_field([
    $option,
    $value
]);

echo "Changed!\n\n";
echo "Saving the change...\n";

// Open the file to save
$dataJsonFile = fopen($dataJson->prefix . "/data.json", "w");
fwrite($dataJsonFile, json_encode($dataJson->data));
fclose($dataJsonFile);

echo "Saved!\n";