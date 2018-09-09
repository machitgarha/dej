<?php

class LoadJSON
{
    // Processed data
    public $data = null;
    
    // Type of data; either object (0) or array (1)
    public $dataType = 0;
    public $defaultDataType;
    const OBJECT_DATA_TYPE = 0;
    const ARRAY_DATA_TYPE = 1;
    
    // File path of the JSON file
    public $prefix = "./config";
    private $filename;
    public $filePath;
    private $isOptional = false;
    
    // Holding directory and names of validation files
    private $validationDir = "./data/validation/";
    public $validationFiles = [
        "type" => "type.json",
        "regex" => "regex.json"
    ];
    
    // Handling error messages
    private $errorMessages = [
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
    private $fullTypes = [
        "mac" => "colon-styled MAC address",
        "int" => "integer",
        "bool" => "boolean",
        "alphanumeric" => "alphanumeric"
    ];
    
    // Loads JSON file and handles data
    public function __construct(string $filePath,
        int $type = self::OBJECT_DATA_TYPE, bool $isOptional = false,
        bool $withPrefix = true, string $additionalInfo = "")
    {
        // Set properties
        $this->filename = $filePath;
        $this->filePath = $withPrefix ? "{$this->prefix}/$filePath" : $filePath;
        $this->defaultDataType = $type;
        $this->isOptional = $isOptional;
        
        // Checks for file existance and readability
        if (!is_readable($this->filePath))
            if ($isOptional)
                return;
            else
                $this->warn("file_reading_error", [
                    "filename" => $this->filePath,
                    "?additional_info" => $additionalInfo
                ], "exit");
        
        // Get data from JSON file as object (0)
        $this->data = json_decode(file_get_contents($this->filePath));
        
        // Join default validation directory to each validation file
        foreach ($this->validationFiles as $key => &$filename)
            $filename = $this->validationDir . $filename;
        
        // Change data type
        $this->change_type();
    }
    
    // Changes type of data from object (0) to array (1) and vice verca
    public function change_type($type = null, &$data = null) {
        // Handling default values
        if ($type === null)
            $type = $this->defaultDataType;
        if ($data === null)
            $data = &$this->data;
        
        $data = json_decode(json_encode($data), $type);
        
        // Update property
        $this->dataType = $type;
    }

    // Requirements of working a validation function
    private function prepare_validation(string $validationType,
        int $returnAs = self::OBJECT_DATA_TYPE, bool $strict = false)
    {
        // If the file is optional, don't continue
        if (!$strict && $this->isOptional)
            return false;
        
        // Find validation file
        $validationFile = $this->validationFiles[$validationType];
        
        // Checks for existance and readability
        if (!is_readable($validationFile))
            $this->warn("file_reading_error", [
                "filename" => $validationFile
            ], "exit");
        
        // Get validation data
        $validationData = json_decode(file_get_contents($validationFile),
            true)[$this->filename] ?? null;
        
        // Warn user if data cannot be validated
        if (!$validationData)
            $this->warn("validation_not_found", [
                "filename" => $this->filePath,
                "validation_type" => $validationType
            ], "exit");

        $this->change_type($returnAs, $validationData);
        return $validationData;
    }
    
    // Handles validation based on field classes (e.g. required)
    public function class_validation(bool $justWarning = false)
    {        
        // Getting things ready and get validation data
        $validationData = $this->prepare_validation("type",
            self::OBJECT_DATA_TYPE);

        // If the file is optional, don't perform checks
        if (!$validationData)
            return false;
        
        // Prepare data
        $data = &$this->data;
        $this->change_type(self::OBJECT_DATA_TYPE);

        // Iteration over all fields
        foreach ($validationData as $fieldName => $fieldData) {
            $fieldClass = $fieldData->class ?? "optional";
            $defaultValue = $fieldData->default_value ?? null;

            // If the field exists, then
            if (!self::get_field($fieldName, $data)) {
                // If field is not required, set the default value for it
                if ($fieldClass !== "required")
                    self::add_field($fieldName, $defaultValue, $data);
                
                // If field is not optional, generate a message
                if ($fieldClass !== "optional")
                    $this->warn("missing_field", [
                        "field" => $fieldName,
                        "filename" => $this->filePath,
                        "?type" => $fieldClass === "required" ? "required" : ""
                    ], (!$justWarning && $fieldClass === "required") ? "exit" : 
                        "warn");
            }
        }
        
        $this->change_type();
    }

    // Handles validations based on regular expressions
    public function regex_validation()
    {        
        // Getting things ready and get validation data
        $validationData = $this->prepare_validation("regex",
            self::OBJECT_DATA_TYPE);

        // If the file is optional, don't perform checks
        if (!$validationData)
            return false;

        // Prepare data
        $data = &$this->data;
        $this->change_type(self::OBJECT_DATA_TYPE);

        // Validates using regular expressions
        foreach ($validationData as $regex)
            // Checks for field expression
            if (preg_match("/[^a-z0-9\._]/i", $regex->field))
                // Parsing data
                foreach ($data as $field => $value)
                    // Perform validation for both fields and values
                    foreach (["field", "value"] as $i)
                        // Warn user if there is an invalid data
                        if (isset($regex->$i) && !preg_match($regex->$i, $$i))
                            $this->warn("validation_failed", [
                                "filename" => $this->filePath,
                                "value" => $$i,
                                "conditions" => implode(PHP_EOL,
                                    $regex->{$i . "_cond"} ??
                                    (array)"Not detailed anymore.")
                            ]);

        $this->change_type();
    }

    // Perform validation for types, and warn for mistypes
    public function type_validation($data = null, $strict = false,
        bool $invalidInput = false)
    {        
        // Getting things ready and get validation data
        $validationData = $this->prepare_validation("type",
            self::OBJECT_DATA_TYPE, $strict);

        // If the file is optional, don't perform checks
        if (!$validationData)
            return false;

        // Prepare data
        if (!$data)
            $data = &$this->data;
        $this->change_type(self::OBJECT_DATA_TYPE);
        
        // Iteration over all fields
        foreach ($validationData as $fieldName => $fieldData) {
            $fieldType = $fieldData->type ?? "string";
            $fieldValue = self::get_field($fieldName, $data, true);

            // Skip if no such field set
            if ($fieldValue === null)
                continue;
            
            // Validate each field by its type
            $validField = true;
            switch ($fieldType) {
                // MAC address
                case "mac":
                    if (!filter_var($fieldValue, FILTER_VALIDATE_MAC) ||
                        preg_match("/[^\da-f\:]/i", $fieldValue))
                        $validField = false;
                    break;

                // Integer
                case "int":
                    if (!filter_var($fieldValue, FILTER_VALIDATE_INT))
                        $validField = false;
                    break;
                
                // Including only alphabets and numbers
                case "alphanumeric":
                    if (preg_match("/[^a-z0-9]/i", $fieldValue))
                        $validField = false;
                    break;
                    
                // Boolean
                case "bool":
                    if (!is_bool($fieldValue))
                        $validField = false;
            }

            // Warn user, there is mistyped field!
            if (!$validField)
                $this->warn($invalidInput ? "invalid_input" : "warn_bad_type", [
                    "field" => $fieldName,
                    "filename" => $this->filePath,
                    "type" => $this->fullTypes[$fieldType],
                    "value" => json_encode($fieldValue)
                ]);
        }
        
        $this->change_type();
    }

    // Check if a field exist in data or not
    public static function get_field(string $fieldName, $data,
        bool $getValue = false)
    {
        // Get its value
        $fieldValue = array_reduce(explode('.', $fieldName),
            function ($object, $property) {
                return $object->$property ?? null;
            }, $data);

        return ($getValue ? $fieldValue : $fieldValue !== null);
    }

    // Adds a field to data with a default value
    public static function add_field(string $fieldName, $value, &$data)
    {        
        // If the file is optional
        if (!$data)
            $data = new stdClass();

        // Split object parts
        $properties = explode(".", $fieldName);

        // Reference to the object
        $ref = &$data;

        // Create properties which they consist some other properties
        $propertiesCount = count($properties);
        for ($i = 0; $i < $propertiesCount - 1; $i++) {
            $propertyName = $properties[$i];

            // Create if not exist
            if (!isset($ref->$propertyName))
                $ref->$propertyName = new stdClass();

            // Update reference to the latest created property
            $ref = &$ref->$propertyName;
        }

        // Set the property, as the last work
        $ref->{$properties[$propertiesCount - 1]} = $value;
    }
        
    // Warn user or exit program with a message
    private function warn(string $messageIndex, array $bindValues,
        string $type = "warn") {
        // Preparing to bind values
        $bindArr = $bindValues;
        $bindValues = [];
        foreach ($bindArr as $key => $val)
            if (!empty($val))
                $bindValues["%$key%"] = $val;
        
        // Preparing output message
        $msg = str_replace(array_keys($bindValues), array_values($bindValues),
            $this->errorMessages[$messageIndex]);
        
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
