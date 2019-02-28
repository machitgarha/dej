<?php

namespace Dej\Element;

use MAChitgarha\Component\JSONFile;
use Webmozart\PathUtil\Path;
use MAChitgarha\Component\JSON;
use Dej\Exception\Exception;
use Dej\Exception\InvalidFieldValueException;
use Dej\Exception\MissingFieldException;
use Dej\Exception\FileNameInvalidException;

class DataValidation
{
    /** @var JSONFile */
    private $json;

    private $warnings = [];
    private $errors = [];

    // Holding directory and names of validation files
    private $validationDir = __DIR__ . "/../../data/validation/";
    private $validationData;

    // Better output for types
    private $fullTypes = [
        "mac" => "colon-styled MAC address",
        "int" => "integer",
        "bool" => "boolean",
        "alphanumeric" => "alphanumeric"
    ];

    // Prepare things
    public function __construct(JSONFile $json)
    {
        $this->json = $json;

        // Open validation file
        $validationJson = new JSONFile(Path::join($this->validationDir, "type.json"));

        // Save validation data
        $escapedFilename = str_replace(".", "\.", $json->getFilename());
        $this->validationData = $validationJson->get($escapedFilename);
    }

    // Handles validation based on field classes (e.g. required)
    public function classValidation(): DataValidation
    {
        // Initialize variables
        $validationData = $this->validationData;
        $json = $this->json->getDataAsObject();

        // Iteration over all fields
        $validate = function (JSON $json) use ($validationData) {
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
                        "file_path" => $json->getFilename()
                    ];
                    if ($fieldClass === "required")
                        $data["?type"] = "required";
                    if ($fieldClass !== "optional")
                        $this->pushError(new MissingFieldException($data));
                }
            }
        };

        switch ($this->json->getFilename()) {
            case "data.json":
                $validate($this->json);
                break;
            
            case "users.json":
                foreach ($this->json->iterate() as $key => $userData)
                    foreach ((array)$userData->mac as $userMac) {
                        $userDataJson = new JSON([
                            "name" => $userData->name,
                            "mac" => $userMac
                        ]);
                        $userDataJson->getFilename = function () {
                            return $this->json->getFilename();
                        };
                        if ($validate($userDataJson))
                            $json->set($key, null);
                    }
                break;

            default:
                throw new FileNameInvalidException([], true);
        }

        return $this;
    }

    // Perform validation for types, and warn for mistypes
    public function typeValidation(): DataValidation
    {
        // Initialize variables
        $validationData = $this->validationData;
        $json = $this->json->getDataAsObject();

        $validate = function (JSON &$json) use ($validationData)
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
                if (!$validField)
                    $this->pushWarning(new InvalidFieldValueException([
                        "type" => $this->fullTypes[$fieldType],
                        "value" => json_encode($fieldValue)
                    ]));
            }
        };

        switch ($this->json->getFilename()) {
            case "data.json":
                $validate($this->json);
                break;
            
            case "users.json":
                foreach ($this->json->iterate() as $userData)
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
        
        return $this;
    }

    public function return(bool $onlyWarnings = false): JSON
    {
        $this->output($onlyWarnings);
        return $this->json;
    }

    private function pushWarning($warning)
    {
        $this->warnings[] = $warning;
    }

    private function pushError($error)
    {
        $this->errors[] = $error;
    }

    public function getWarnings(bool $getErrors = true): array
    {
        $warnings = $this->warnings;
        if ($getErrors)
            $warnings = array_merge($warnings, $this->errors);

        return $warnings;
    }

    public function output(bool $onlyWarnings = false)
    {
        $sh = new ShellOutput();

        $errorsOutputType = $onlyWarnings ? "warn" : "error";
        foreach ($this->errors as $error)
            $sh->$errorsOutputType($error);

        foreach ($this->warnings as $warning)
            $sh->warn($warning);
    }

    public static function new(JSONFile $json): self
    {
        return new self($json);
    }
}