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
        "file_reading_error" => "Cannot read %filename%.",
        "validation_not_found" => "Cannot validate %filename% file with"
            . " '%validation_type%' validation type.",
        "missing_field" => "Missing %?type% '%field%' field in %filename%.",
        "warn_set_field" => "Missing '%field%' field in %filename%."
            . "\nSetting it to '%default%' (default value).",
        "validation_failed" => "Wrong field was set. '%value%' must:\n"
            . "%conditions%."
    ];
    
    // Loads JSON file and handles data
    public function __construct(string $filePath,
        int $type = self::OBJECT_DATA_TYPE, bool $isOptional = false,
        bool $withPrefix = true)
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
                    "filename" => $this->filePath
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
        int $returnAs = self::OBJECT_DATA_TYPE)
    {
        // If the file is optional, don't continue
        if ($this->isOptional)
            return false;

        $this->change_type(self::OBJECT_DATA_TYPE);
        
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
    
    // Handles validation based on field types
    public function type_validation(bool $justWarning = false)
    {
        $data = &$this->data;
        
        // Getting things ready and get validation data
        $validationData = $this->prepare_validation("type",
            self::ARRAY_DATA_TYPE);

        // If the file is optional, don't perform checks
        if (!$validationData)
            return false;
        
        // Handles required fields
        foreach ($validationData["required"] as $field)
            // Checks if a field is available or not
            if (!$this->get_field($field, $data))
                // Exit program
                $this->warn("missing_field", [
                    "field" => $field,
                    "filename" => $this->filePath,
                    "?type" => "required"
                ], $justWarning ? "warn" : "exit");
            
        // Handles warning fields
        foreach ($validationData["warning"] as $field)
            // Checks if a field is available or not
            if (!$this->get_field($field[0], $data)) {
                // Warn user
                $this->warn($justWarning ? "missing_field" : "warn_set_field", [
                    "field" => $field[0],
                    "filename" => $this->filePath,
                    "default" => $field[1]
                ]);
                    
                $this->add_field($field, $data);
            }
            
        // Handles optional fields
        foreach ($validationData["optional"] as $field)
            if (!$this->get_field($field[0], $data))
                $this->add_field($field, $data);
        
        $this->change_type();
    }

    // Check if a field exist in data or not
    public function get_field(string $field, $data = null, $getValue = false)
    {
        // Default value
        if (!$data)
            $data = $this->data;

        // Get its value
        $fieldValue = array_reduce(explode('.', $field),
            function ($object, $property) {
                return $object->$property ?? null;
            }, $data);

        if ($getValue)
            return $fieldValue;

        // Return if field exists or not
        return $fieldValue !== null;
    }

    // Adds a field to data with a default value
    public function add_field(array $field, &$data = null)
    {
        // Default value
        if (!$data)
            $data = &$this->data;
        
        // If the file is optional
        if (!$data)
            $data = new stdClass();

        // Split object parts
        $properties = explode(".", $field[0]);

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
        $ref->{$properties[$propertiesCount - 1]} = $field[1];
    }
    
    // Handles validations based on regular expressions
    public function regex_validation()
    {
        $data = &$this->data;
        
        // Getting things ready and get validation data
        $validationData = $this->prepare_validation("regex",
            self::OBJECT_DATA_TYPE);

        // If the file is optional, don't perform checks
        if (!$validationData)
            return false;

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
        
    // Warn user or exit program with a message
    private function warn(string $messageIndex, array $bindValues,
        string $type = "warn") {
        // Preparing to bind values
        $bindArr = $bindValues;
        $bindValues = [];
        foreach ($bindArr as $key => $val)
            $bindValues["%$key%"] = $val;
        
        // Preparing output message
        $msg = str_replace(array_keys($bindValues), array_values($bindValues),
            $this->errorMessages[$messageIndex] . PHP_EOL);
        
        // Skip optional output parameters
        $msg = preg_replace("/%\?.*%\s/", "", $msg);
        
        // Handles the type of printing message
        switch ($type) {
            // Exits program
            case "exit":
            exit("Error: $msg");
            
            // Just print out the message
            case "warn":
            echo("Warning: $msg");
            break;
        }
    }
}
