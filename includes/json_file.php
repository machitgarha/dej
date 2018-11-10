<?php

class JSONFile extends JSON
{
    // File information
    public $filename;
    public $filePath;

    public function __construct(string $filename, string $prefixPath = ".",
        int $defaultDataType = self::OBJECT_DATA_TYPE, bool $strictTypes = true)
    {
        switch ($defaultDataType) {
            case self::JSON_DATA_TYPE:
            case self::OBJECT_DATA_TYPE:
            case self::ARRAY_DATA_TYPE:
                break;
            
            default:
                throw new InvalidArgumentException("Unknown default data type passed");
        }

        // Save file information
        $this->filename = $filename;
        $putSlash = !in_array(substr($prefixPath, -1), ["/", "\\"]);
        $this->filePath = $prefixPath . ($putSlash ? "/" : "") . $filename;

        // Read JSON file
        $data = $this->read();

        parent::__construct($data, $defaultDataType, $strictTypes);
    }

    // Checks for file existance and readability
    private function check(string $for = "read")
    {
        $filePath = $this->filePath;

        // Check file existance
        if (!file_exists($filePath))
            throw new FileExistanceException("File '$filePath' doesn't exist");

        // Check for readability
        if ($for === "read" && !is_readable($filePath))
            throw new FileReadingException("Cannot read from '$filePath'");
        
        // Check for writability
        if ($for === "write" && !is_writable($filePath))
            throw new FileWritingException("Cannot write into '$filePath'");
    
        return true;
    }

    // Read from the file
    private function read()
    {
        // Check if the file can be read
        $this->check();

        // Open the file and read it
        $filePath = $this->filePath;
        $file = fopen($filePath, "r");
        if (!$file)
            throw new FileOpeningException("Cannot open '$filePath' for reading");
        $data = @fread($file, filesize($filePath));
        fclose($file);

        // To prevent saving as a pretty-printed data
        return json_encode(json_decode($data));
    }

    // Save the file
    public function save($saveOptions = JSON_PRETTY_PRINT)
    {
        // Check if the file can be read
        $this->check("write");

        // Open the file and read it
        $filePath = $this->filePath;
        $file = fopen($filePath, "w");
        if (!$file)
            throw new FileOpeningException("Cannot open '$filePath' for writing");
        fwrite($file, $this->to(self::JSON_DATA_TYPE, true, $saveOptions));
        fclose($file);

        return true;
    }

    public function to(int $type = self::DEFAULT_DATA_TYPE, bool $temp = false,
        $options = null)
    {
        $data = parent::to($type, $temp);

        // If there are some options, like JSON_PRETTY_PRINT
        if ($type === self::JSON_DATA_TYPE && $options !== null)
            $data = json_encode(json_decode($data), $options);

        return $data;
    }
}