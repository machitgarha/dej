<?php

class DataValidation
{
    // Holding directory and names of validation files
    private static $validationDir = "./data/validation/";
    private static $validationFilenames = [
        "type" => "type.json"
    ];

    // Better output for types
    private static $fullTypes = [
        "mac" => "colon-styled MAC address",
        "int" => "integer",
        "bool" => "boolean",
        "alphanumeric" => "alphanumeric"
    ];
    
    // Requirements of working a validation function
    private static function prepare_validation(JSON $json, string $validationType,
        int $returnAs = JSON::OBJECT_DATA_TYPE)
    {
        $sh = new Shell();

        // Find validation file
        $validationFile = self::$validationDir . self::$validationFilenames[$validationType];
        
        // Checks for existance and readability
        if (!is_readable($validationFile))
            $sh->error();

        // Get validation data
        $validationData = json_decode(file_get_contents($validationFile),
            true)[$json->filename] ?? null;

        // Warn user if data cannot be validated
        if (!$validationData)
            $sh->error();

        // Convert it to proper type (e.g., object)
        return (new JSON($validationData, $returnAs))->data;
    }
    
    // Handles validation based on field classes (e.g. required)
    public static function class_validation(JSON &$json, bool $onlyWarn = false)
    {
        $sh = new Shell();
        $foundWarning = false;

        // Getting things ready and get validation data
        $validationData = self::prepare_validation($json, "type");

        // Prepare data
        $data = &$json->data;
        $json->to(JSON::OBJECT_DATA_TYPE);

        // Iteration over all fields
        $validate = function (JSON $json) use ($validationData, $onlyWarn, $sh, $foundWarning) {
            foreach ($validationData as $fieldName => $field) {
                // Get its value (it may be null, it will be checked in the closure)
                $fieldValue = $json->get($fieldName);

                // Hold properties for better access
                $fieldClass = $field->class ?? "optional";
                $defaultValue = $field->default_value ?? null;

                // If it wasn't set, perform fixings/warnings
                if (!$json->is_set($fieldName)) {
                    // If field is not required, set the default value for it
                    if ($fieldClass !== "required")
                        $json->set($fieldName, $defaultValue);

                    $data = [
                        "field_name" => $fieldName,
                        "file_path" => $json->filePath
                    ];
                    if ($fieldClass === "required")
                        $data = array_merge($data, ["?type" => "required"]);
                    if ($fieldClass !== "optional") {
                        $foundWarning = true;
                        if ($onlyWarn)
                            $sh->warn(new MissingFieldException($data));
                        elseif ($fieldClass === "required")
                            throw new MissingFieldException($data);
                    }
                }
            }

            return $foundWarning;
        };

        switch ($json->filename) {
            case "data.json":
                return $validate($json);
                break;
            
            case "users.json":
                foreach ($json->iterate() as $userData) {
                    $userDataJson = new JSON($userData);
                    $userDataJson->filename = $json->filename;
                    $validate($userDataJson);
                }
                break;

            default:
                throw new FileNameInvalidException([], true);
        }

        $json->to();
    }

    // Perform validation for types, and warn for mistypes
    public static function type_validation(JSON $json, bool $invalidInput = false)
    {
        $sh = new Shell();
        $foundWarning = false;

        // Getting things ready and get validation data
        $validationData = self::prepare_validation($json, "type");

        // Prepare data
        $data = &$json->data;
        $json->to(JSON::OBJECT_DATA_TYPE);

        $validate = function (JSON $json) use ($validationData, $invalidInput, $sh, $foundWarning) {
            // Iteration over all fields
            foreach ($validationData as $fieldName => $field) {
                // Get its value (it may be null, it will be checked in the closure)
                $fieldValue = $json->get($fieldName);

                // Hold properties for better access
                $fieldType = $field->type ?? "string";

                // Skip if no such field set
                if ($fieldValue === null)
                    return;

                // Validate each field by its type
                $validField = true;
                switch ($fieldType) {
                    // MAC address
                    case "mac":
                        if (!preg_match("/^([\da-f]{2}:){5}([\da-f]{2})$/i",
                            $fieldValue))
                            $validField = false;
                        break;

                    // Integer
                    case "int":
                        if (!filter_var($fieldValue, FILTER_VALIDATE_INT))
                            $validField = false;
                        break;
                    
                    // Including only alphabets and numbers
                    case "alphanumeric":
                        if (!preg_match("/^[\w\-\s\.]+$/i", $fieldValue))
                            $validField = false;
                        break;
                        
                    // Boolean
                    case "bool":
                        if (!is_bool($fieldValue))
                            $validField = false;
                }

                // Warn user, there is mistyped field!
                if (!$validField) {
                    $foundWarning = true;
                    $sh->warn(new InvalidFieldValueException([
                        "type" => self::$fullTypes[$fieldType],
                        "value" => json_encode($fieldValue)
                    ]));
                }
            }

            return $foundWarning;
        };

        switch ($json->filename) {
            case "data.json":
                return $validate($json);
                break;
            
            case "users.json":
                foreach ($json->iterate() as $userData) {
                    $userDataJson = new JSON($userData);
                    $userDataJson->filename = $json->filename;
                    $validate($userDataJson);
                }
                break;

            default:
                throw new FileNameInvalidException([], true);
        }
        
        $json->to();
    }
}