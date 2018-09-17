<?php

class JSON
{
    // Processed data
    public $data = "";

    // Data type configurarions
    private $currentDataType = self::JSON_DATA_TYPE;
    public $defaultDataType = self::JSON_DATA_TYPE;
    const JSON_DATA_TYPE = 0;
    const OBJECT_DATA_TYPE = 1;
    const ARRAY_DATA_TYPE = 2;
    const DETECT_DATA_TYPE = 3;
    const DEFAULT_DATA_TYPE = 4;

    // Loads JSON file and handles data
    public function __construct($data = null,
        int $defaultDataType = self::DETECT_DATA_TYPE, bool $strictTypes = true)
    {
        switch ($defaultDataType) {
            case self::JSON_DATA_TYPE:
            case self::OBJECT_DATA_TYPE:
            case self::ARRAY_DATA_TYPE:
            case self::DETECT_DATA_TYPE:
                break;
            
            default:
                throw new InvalidArgumentException("Unknown default data type passed");
        }

        if ($data === null)
            return;

        // Prevent from data to be either a string, an array or an object
        $isString = is_string($data);
        $isArray = is_array($data);
        $isObject = is_object($data);
        if (!($isString || $isArray || $isObject) && $strictTypes)
            throw new InvalidArgumentException("Wrong data type.");

        $this->data = $data;

        // Save current data type
        $this->currentDataType = $isString ? self::JSON_DATA_TYPE :
            ($isArray ? self::ARRAY_DATA_TYPE : self::OBJECT_DATA_TYPE);

        // Detect data type; when data is null, set type to object
        if ($defaultDataType === self::DETECT_DATA_TYPE)
            $this->defaultDataType = $defaultDataType = $this->currentDataType;
        else
            $this->defaultDataType = $defaultDataType;

        // Change data type
        $this->to($defaultDataType);
    }

    // Change the data type
    public function to(int $type = self::DEFAULT_DATA_TYPE, bool $temp = false)
    {
        // Use default data type
        if ($type === self::DEFAULT_DATA_TYPE)
            $type = $this->defaultDataType;

        // Don't change type if currect data type equals the requested type
        if ($type === $this->currentDataType)
            return $this->data;

        // To use it in the future
        $data = $this->data;

        switch ($type) {
            // Convert to JSON string
            case self::JSON_DATA_TYPE:
                $data = json_encode($data);
                break;

            // Convert to either array or object
            case self::ARRAY_DATA_TYPE:
            case self::OBJECT_DATA_TYPE:
                // If current data is JSON string, simply decode it
                if ($this->currentDataType === self::JSON_DATA_TYPE)
                    $data = json_decode($data);

                // Now, perform convertion
                $data = json_decode(json_encode($data, JSON_FORCE_OBJECT),
                    (bool)($type - 1));
                break;

            default:
                throw new InvalidArgumentException("Wrong type");
                break;
        }

        // Change current data
        if (!$temp) {
            $this->currentDataType = $type;
            $this->data = $data;
        }

        // Return changed data
        return $data;
    }

    // Get field's value, by given parts
    private function field(string $fieldName, $data = null)
    {
        // Set default data
        if ($data === null)
            $data = $this->data;

        $fieldIndexes = explode(".", $fieldName);

        // Find the field to match with field indexes
        return array_reduce($fieldIndexes,
            function ($curItVal, $property) {
                return $curItVal->$property ?? null;
            }, $data);
    }

    // Iterates over
    public function iterate(string $index = "")
    {
        // Split object parts
        $properties = explode(".", $index);

        // Reference to the object
        $data = $this->to(self::ARRAY_DATA_TYPE, true);

        // Create properties which they consist some other properties
        if ($index !== "")
            foreach ($properties as $property) {
                // Create if not exist
                if (!isset($data[$property]))
                    throw new InvalidArgumentException("Invalid index");

                // Update reference to the latest created property
                $data = $data[$property];
            }

        if (!is_array($data))
            throw new TypeError("Reached non-iterable value");

        foreach ($data as $key => $val) {
            yield $key => (new self(json_encode($val), $this->currentDataType, false))->data;
        }
    }

    // Return a field's value, and can be nested by dots
    public function get(string $fieldName)
    {
        $data = $this->to(self::OBJECT_DATA_TYPE, true);

        // Explode parts by dots
        return $this->field($fieldName, $data);
    }

    // Check for a field's existance, and can be nested by dots
    public function is_set(string $fieldName)
    {
        // Explode parts by dots
        $value = $this->field($fieldName, $this->to(self::OBJECT_DATA_TYPE));

        return $value !== null;
    }

    // Set or change a field's value
    public function set(string $fieldName, $value)
    {
        // Split object parts
        $properties = explode(".", $fieldName);

        // Reference to the object
        $this->to(self::ARRAY_DATA_TYPE);
        $ref = &$this->data;

        // Create properties which they consist some other properties
        $to = count($properties) - 1;
        for ($i = 0; $i < $to; $i++) {
            $property = $properties[$i];

            // Create if not exist
            if (!isset($ref[$property]))
                $ref[$property] = [];

            // Update reference to the latest created property
            $ref = &$ref[$property];
        }

        // Set the property, as the last work
        $ref[$properties[$to]] = $value;

        $this->to();
    }

    public function __toString()
    {
        return $this->to(self::JSON_DATA_TYPE, true);
    }
}
