<?php

// Prints an output with a new line at the end
function echol(string $str = "", int $newLinesCount = 1) {
    echo $str;
    for ($i = 0; $i < $newLinesCount; $i++)
        echo PHP_EOL;
}

// Prints an output with a new line at the end and exits
function exitl(string $str) {
    exit($str . PHP_EOL);
}

// Warn user, if needed, exit executing
function warn(string $messageIndex, array $bindValues, string $type = "warn") {
    // Handling error messages
    $errorMessages = [
        "internal_error" => "Interal error occured.",
        "file_reading_error" => "Cannot read %filename%.\n%?additional_info%",
        "validation_not_found" => "Cannot validate %filename% file with '%validation_type%'\n" .
            "validation type.",
        "missing_field" => "Missing %?type% '%field%' field in %filename%.",
        "validation_failed" => "Wrong field was set. '%value%' must:\n%conditions%.",
        "warn_bad_type" => "'%field%' field in %filename% is invalid. It must\n"
            . "be a(n) %type%. Current value: %value%",
        "invalid_input" => "%value% is not a valid %type%.\n"
    ];

    // Preparing to bind values
    $bindArr = $bindValues;
    $bindValues = [];
    foreach ($bindArr as $key => $val)
        if (!empty($val))
            $bindValues["%$key%"] = $val;

    // Preparing output message
    $msg = str_replace(array_keys($bindValues), array_values($bindValues),
        $errorMessages[$messageIndex] ?? $messageIndex);

    // Skip optional output parameters
    $msg = preg_replace("/\s*%\?.+%/", "", $msg);

    $msg .= PHP_EOL;

    // Handles the type of printing message
    switch ($type) {
        // Exit program
        case "exit":
            exit("Error: $msg");
        
        // Warn user
        case "warn":
            echo "Warning: $msg";
            break;
    }
}