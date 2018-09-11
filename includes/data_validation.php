<?php

class DataValidation
{
    // Holding directory and names of validation files
    private static $validationDir = "./data/validation/";
    private static $validationFilenames = [
        "type" => "type.json"
    ];

    // Handling error messages
    private static $errorMessages = [
        "file_reading_error" => "Cannot read %filename%.\n%?additional_info%",
        "validation_not_found" => "Cannot validate %filename% file with"
            . " '%validation_type%' validation type.",
        "missing_field" => "Missing %?type% '%field%' field in %filename%.",
        "validation_failed" => "Wrong field was set. '%value%' must:\n"
            . "%conditions%.",
        "warn_bad_type" => "'%field%' field in %filename% is invalid. It must\n"
            . "be a(n) %type%. Current value: %value%",
        "invalid_input" => "%value% is not a valid %type%.\n"
    ];

    // Better output for types
    private static $fullTypes = [
        "mac" => "colon-styled MAC address",
        "int" => "integer",
        "bool" => "boolean",
        "alphanumeric" => "alphanumeric"
    ];
    
    // Requirements of working a validation function
    private static function prepare_validation(JSON $jsonHandler,
        string $validationType, int $returnAs = JSON::OBJECT_DATA_TYPE)
    {
        // Find validation file
        $validationFile = self::$validationDir .
            self::$validationFilenames[$validationType];
        
        // Checks for existance and readability
        if (!is_readable($validationFile))
            self::warn("file_reading_error", [
                "filename" => $validationFile
            ], "exit");

        // Get validation data
        $validationData = json_decode(file_get_contents($validationFile),
            true)[$jsonHandler->filename] ?? null;
        
        // Warn user if data cannot be validated
        if (!$validationData)
            self::warn("validation_not_found", [
                "filename" => var_dump(debug_backtrace()),
                "validation_type" => $validationType
            ], "exit");

        // Convert it to proper type (e.g., object)
        return (new JSON($validationData, $returnAs))->data;
    }

    // Remove all stars from the field name for output
    private static function extract_field_name(string &$fieldName)
    {
        $fieldName = preg_replace("/\.?\*\.?/i", "", $fieldName);
    }
    
    // Handles validation based on field classes (e.g. required)
    public static function class_validation(JSON &$jsonHandler,
        bool $justWarning = false)
    {
        // Getting things ready and get validation data
        $validationData = self::prepare_validation($jsonHandler, "type");

        // Prepare data
        $data = &$jsonHandler->data;
        $jsonHandler->change_type(JSON::OBJECT_DATA_TYPE);

        // Fix or warn on a field
        $fieldCheck = function ($field, $fieldName, $value)
            use ($jsonHandler, $justWarning) {
            // Hold properties for better access
            $fieldClass = $field->class ?? "optional";
            $defaultValue = $field->default_value ?? null;

            // If it wasn't set, perform fixings/warnings
            if (!$jsonHandler->is_set($field->is_set ?? $fieldName)) {
                self::extract_field_name($fieldName);

                // If field is not required, set the default value for it
                if ($fieldClass !== "required")
                    $jsonHandler->set($fieldName, $defaultValue);
                
                // If field is not optional, generate a message
                if ($fieldClass !== "optional")
                    self::warn("missing_field", [
                        "field" => $fieldName,
                        "filename" => $jsonHandler->filePath,
                        "?type" => $fieldClass === "required" ? "required" : ""
                    ], (!$justWarning && $fieldClass === "required") ? "exit" : 
                        "warn");
            }
        };

        // Iteration over all fields
        foreach ($validationData as $fieldName => $fieldData) {
            // Get its value (it may be null, it will be checked in the closure)
            $fieldValue = $jsonHandler->get($fieldName);

            // If it's an array, check all values
            if (is_array($fieldValue))
                foreach ($fieldValue as $fieldItem)
                    $fieldCheck($fieldData, $fieldName, $fieldItem);
            else
                $fieldCheck($fieldData, $fieldName, $fieldValue);
        }

        $jsonHandler->change_type();
    }

    // Perform validation for types, and warn for mistypes
    public static function type_validation(JSON $jsonHandler,
        bool $invalidInput = false)
    {        
        // Getting things ready and get validation data
        $validationData = self::prepare_validation($jsonHandler, "type");

        // Prepare data
        $data = &$jsonHandler->data;
        $jsonHandler->change_type(JSON::OBJECT_DATA_TYPE);

        $fieldCheck = function ($field, $fieldName, $value)
            use ($jsonHandler, $invalidInput) {
            // Hold properties for better access
            $fieldType = $field->type ?? "string";

            // Skip if no such field set
            if ($value === null)
                return;

            // Validate each field by its type
            $validField = true;
            switch ($fieldType) {
                // MAC address
                case "mac":
                    if (!preg_match("/^([\da-f]{2}:){5}([\da-f]{2})$/i",
                        $value))
                        $validField = false;
                    break;

                // Integer
                case "int":
                    if (!filter_var($value, FILTER_VALIDATE_INT))
                        $validField = false;
                    break;
                
                // Including only alphabets and numbers
                case "alphanumeric":
                    if (!preg_match("/^[a-z0-9]+$/i", $value))
                        $validField = false;
                    break;
                    
                // Boolean
                case "bool":
                    if (!is_bool($value))
                        $validField = false;
            }

            // Warn user, there is mistyped field!
            self::extract_field_name($fieldName);
            if (!$validField)
                self::warn($invalidInput ? "invalid_input" : "warn_bad_type", [
                    "field" => $fieldName,
                    "filename" => $jsonHandler->filePath,
                    "type" => self::$fullTypes[$fieldType],
                    "value" => json_encode($value)
                ]);
        };

        // Iteration over all fields
        foreach ($validationData as $fieldName => $fieldData) {
            // Get its value (it may be null, it will be checked in the closure)
            $fieldValue = $jsonHandler->get($fieldName);

            // If it's an array, check all values
            if (is_array($fieldValue))
                foreach ($fieldValue as $fieldItem)
                    $fieldCheck($fieldData, $fieldName, $fieldItem);
            else
                $fieldCheck($fieldData, $fieldName, $fieldValue);
            
        }
        
        $jsonHandler->change_type();
    }
    
    // Warn user or exit program with a message
    private static function warn(string $messageIndex, array $bindValues,
        string $type = "warn") {
        // Preparing to bind values
        $bindArr = $bindValues;
        $bindValues = [];
        foreach ($bindArr as $key => $val)
            if (!empty($val))
                $bindValues["%$key%"] = $val;
        
        // Preparing output message
        $msg = str_replace(array_keys($bindValues), array_values($bindValues),
            self::$errorMessages[$messageIndex]);
        
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
}