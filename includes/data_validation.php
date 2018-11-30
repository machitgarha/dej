<?php

class DataValidation
{
    private $json;

    // Holding directory and names of validation files
    private $validationDir = "./data/validation/";
    private $validationData;

    // Better output for types
    private $fullTypes = [
        "mac" => "colon-styled MAC address",
        "int" => "integer",
        "bool" => "boolean",
        "alphanumeric" => "alphanumeric"
    ];

    // Prepare things
    public function __construct(JSON &$json)
    {
        $this->json = $json;

        $this->sh = new Shell();

        // Open validation file
        $validationJson = new JSONFile("type.json", $this->validationDir);

        // Save validation data
        $this->validationData = $validationJson->data->{$this->json->filename};
    }

    // Handles validation based on field classes (e.g. required)
    public function classValidation(bool $onlyWarn = false)
    {
        $foundWarning = false;

        // Initialize variables
        $json = &$this->json;
        $sh = $this->sh;
        $validationData = $this->validationData;
        $json->to(JSON::OBJECT_DATA_TYPE);

        // Iteration over all fields
        $validate = function (JSON &$json) use ($validationData, $onlyWarn, $sh, $foundWarning) {
            foreach ($validationData as $fieldName => $field) {
                // Get its value (it may be null, it will be checked in the closure)
                $fieldValue = $json->get($fieldName);

                // Hold properties for better access
                $fieldClass = $field->class ?? "optional";
                $defaultValue = $field->default_value ?? null;

                // If it wasn't set, perform fixings/warnings
                if (!$json->isSet($fieldName)) {
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
                foreach ($json->iterate() as $key => $userData)
                    foreach ((array)$userData->mac as $userMac) {
                        $userDataJson = new JSON([
                            "name" => $userData->name,
                            "mac" => $userMac
                        ]);
                        $userDataJson->filePath = $json->filePath;
                        if ($validate($userDataJson))
                            $json->set($key, null);
                    }
                break;

            default:
                throw new FileNameInvalidException([], true);
        }

        $json->to();
    }

    // Perform validation for types, and warn for mistypes
    public function typeValidation(bool $invalidInput = false)
    {
        $foundWarning = false;

        // Initialize variables
        $json = &$this->json;
        $sh = $this->sh;
        $validationData = $this->validationData;
        $json->to(JSON::OBJECT_DATA_TYPE);

        $validate = function (JSON &$json) use ($validationData, $invalidInput, $sh, $foundWarning)
        {
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
                        "type" => $this->fullTypes[$fieldType],
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
                foreach ($json->iterate() as $userData)
                    foreach ((array) $userData->mac as $userMac) {
                        $user = new JSON([
                            "name" => $userData->name,
                            "mac" => $userMac
                        ]);
                        $validate($user);
                    }
                break;

            default:
                throw new FileNameInvalidException([], true);
        }
        
        $json->to();
    }
}