<?php

class JSON
{
    // Processed data
    public $data;

    // Type of data; either object (0) or array (1)
    public $defaultDataType;
    const OBJECT_DATA_TYPE = 0;
    const ARRAY_DATA_TYPE = 1;
    const DETECT_DATA_TYPE = 2;

    // File path of the JSON file
    public $prefix = "./config";
    public $filename;
    public $filePath;

    // Loads JSON file and handles data
    function __construct($data = null, int $type = self::DETECT_DATA_TYPE)
    {
        // Prevent from data to be none of null, an array or an object
        $isArray = is_array($data);
        $isObject = is_object($data);
        if (!($data === null || $isArray || $isObject))
            throw new Exception("Wrong data type.");

        // Detect data type; when data is null, set type to object
        if ($type === self::DETECT_DATA_TYPE)
            $this->defaultDataType = $isArray ? self::ARRAY_DATA_TYPE :
                self::OBJECT_DATA_TYPE;

        // Save information into the class property
        $this->data = $data;

        // Change data type
        $this->change_type();
    }

    public function load_file(string $filePath,bool $isOptional = false,
        bool $withPrefix = true, string $additionalInfo = "")
    {
        // Set properties
        $this->filename = $filePath;
        $this->filePath = $withPrefix ? "{$this->prefix}/$filePath" : $filePath;
        
        // Checks for file existance and readability
        if (!is_readable($this->filePath))
            if ($isOptional)
                return;
            else
                exit("Cannot read $this->filePath.\n$additionalInfo");

        // Get data from JSON file as object (0)
        $this->data = json_decode(file_get_contents($this->filePath));
                
        // Change data type to its default (i.e. user choice)
        $this->change_type();
    }
    
    // Changes type of data from object (0) to array (1) and vice verca
    public function change_type($type = null, bool $temp = true) {
        // Handling default values
        if ($type === null)
            $type = $this->defaultDataType;
        
        // Check if to change it permanently or no
        if (!$temp)
            $this->defaultDataType = $type;

        $this->data = json_decode(json_encode($this->data, JSON_FORCE_OBJECT),
            $type);
    }

    // Get field's value, by given parts
    private function operate_field(array $fieldIndexes, $data = null)
    {
        // Set default data
        if ($data === null)
            $data = $this->data;

        // Return data if there is no fields to go
        if (empty($fieldIndexes))
            return $data;

        // Find the field to match with field indexes
        return array_reduce($fieldIndexes,
            // A closure to find the value(s)
            function ($curItVal, $property) use ($fieldIndexes) {
                // Cut field indexes for recursion
                static $cutIndex = 0;
                $cutIndex++;

                // To continue this iteration or not
                static $continue = true;
                if (!$continue)
                    return $curItVal;

                // * means to iterate over all fields and return all matches
                if ($property === "*") {
                    // Cut field indexes for next recursive call,
                    // i.e. ignore this match for next recursion
                    $newFieldIndexes = array_slice($fieldIndexes, $cutIndex);

                    // Move next iterations to recursion
                    $continue = false;

                    // If there is just one value, return it,
                    // Otherwise, go to iterate it
                    if (!is_object($curItVal))
                        return $curItVal;

                    // Recurse all object members (as we are in * property)
                    $arr = [];
                    foreach ($curItVal as $value) {
                        // Search for remain fields in iterated values
                        $val = $this->operate_field($newFieldIndexes, $value);

                        if ($val === null)
                            continue;

                        // Handle how to should the output be
                        if (is_array($val))
                            $arr = array_merge($arr, $val);
                        elseif (is_string($val))
                            $arr[] = (string)$val;
                        else
                            $arr[] = (array)$val;
                    }

                    // Return all matches
                    return $arr;
                }

                // Return the property in the current value
                return $curItVal->$property ?? null;
            }, $data);
    }

    // Return a field's value, and can be nested by dots
    public function get(string $fieldName)
    {
        $this->change_type(self::OBJECT_DATA_TYPE);

        // Explode parts by dots
        $value = $this->operate_field(explode(".", $fieldName));

        $this->change_type();

        return $value;
    }

    // Check for a field's existance, and can be nested by dots
    public function is_set(string $fieldName)
    {
        $this->change_type(self::OBJECT_DATA_TYPE);

        // Explode parts by dots
        $fieldParts = explode(".", $fieldName);
        $value = $this->operate_field($fieldParts);

        $this->change_type(self::ARRAY_DATA_TYPE);
        $data = $this->data;

        $this->change_type();

        // If checking all matches, check if there are any missing matches
        if (in_array("*", $fieldParts))
            return count($value) === count($data);

        return !($value === null || $value === []);
    }

    // Set or change a field's value
    public function set(string $fieldName, $value)
    {
        // Split object parts
        $properties = explode(".", $fieldName);

        // Reference to the object
        $ref = &$this->data;

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
}
