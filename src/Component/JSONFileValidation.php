<?php
/**
 * Dej component file.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Component;

use MAChitgarha\Component\JSONFile;
use Webmozart\PathUtil\Path;
use Dej\Exception\FileNameInvalidException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validating data in configuration files.
 * 
 * Don't try to make it dynamic, as it contains file-specific checks.
 * So don't waste your time to make it possible for every new and strange file.
 * Alert: Every warning or every error is an alert.
 */
class JSONFileValidation extends JSONFile
{
    /** @var int An option has a value with an invalid type. */
    const VALIDATING_INVALID_TYPE = 0;
    /** @var int A required option is missing. */
    const VALIDATING_MISSING_REQUIRED = 1;

    /** @var int */
    const ALERT_TYPE_WARNING = 0;
    /** @var int */
    const ALERT_TYPE_ERROR = 1;

    /** @var array List of alerts (i.e. errors/warnings) found while validating. */
    protected $alerts = [
        "warnings" => [],
        "errors" => [],
    ];

    /** @var string Validation directory which contains validation files. */
    protected $validationDir = __DIR__ . "/../../data/validation/";
    /** @var array Validation data fetched from the validation files. */
    protected $validationData;

    /**
     * Set up validation.
     * 
     * Fetch validation data, save it and set class properties.
     *
     * @param string $filePath File path to be read.
     * @param int $options Available options: FILE_MUST_EXIST, IGNORE_INVALID_FILE
     * @throws \Exception When the file doesn't exist and FILE_MUST_EXIST is on.
     * @throws \Exception When the file contains invalid JSON and IGNORE_INVALID_FILE is off.
     * @throws \Exception When something goes wrong with fetching validation data.
     */
    public function __construct(string $filePath, int $options = 0)
    {
        // Open validation file
        $validationJson = new JSONFile(Path::join($this->validationDir, "type.json"));

        parent::__construct($filePath, $options);

        // Save validation data
        $escapedFilename = str_replace(".", "\.", $this->getFilename());
        $this->validationData = $validationJson->get($escapedFilename);
    }

    /**
     * Validate values of options to have proper types.
     * 
     * @return self
     */
    public function validateTypesOfValues(): self
    {
        /*
         * Based on the configuration file and its structure, validate it.
         * See the example for each file in the examples directory to know files' structures.
         */
        switch ($this->getFilename()) {
            case "data.json":
                foreach ($this->validationData as $optionName => $data)
                    $this->hasValidType($optionName);
                break;
    
            case "users.json":
                foreach ($this->iterate() as $userData) {
                    // Validating users' names
                    $this->hasValidType("name", $userData->name);

                    // Validating users' MAC addresses
                    foreach ((array)$userData->mac as $userMac)
                        $this->hasValidType("macAddress", $userMac);
                }
                break;

            default:
                throw new FileNameInvalidException([], true);
        }

        return $this;
    }

    /**
     * Validates an option to have a proper type.
     *
     * @param string $optionName The option's name.
     * @param mixed $optionValue The option's value.
     * @return bool Returns true if no alerts found and the value is not null, false otherwise.
     */
    public function hasValidType(string $optionName, $optionValue = null): bool
    {
        /*
         * Get the value of the option automatically when the value is null.
         * If the option cannot be found or it's not defined, return false.
         */
        if ($optionValue === null) {
            $optionValue = $this->get($optionName);
            if ($optionValue === null)
                return false;
        }

        // Find option's type (the default type for every option is string)
        $optionData = $this->validationData->$optionName;
        $validType = $optionData->type ?? "string";
        $optionType = gettype($optionValue);

        // Determines whether to push a new error or a warning
        $strictType = $optionData->strictType ?? true;
        $alertType = $strictType ? self::ALERT_TYPE_ERROR : self::ALERT_TYPE_WARNING;

        // Expected type for the option, in a more human-readable way
        $expectedType = $validType;

        switch ($validType) {
            case "string":
            case "integer":
            case "boolean":
                if ($optionType === $validType)
                    return true;
                break;

            case "alphanumeric":
                if (preg_match("/^[0-9a-z]+$/i", $optionValue))
                    return true;
                $expectedType = "alphanumeric string";
                break;

            // Mac addresses could only be colon-styled ones
            case "macAddress":
                if (preg_match("/^([\da-f]{2}:){5}([\da-f]{2})$/i", $optionValue))
                    return true;
                $expectedType = "colon-styled MAC address";
                break;
            
            default:
                throw new \Exception("Unknown type.");
        }

        // Push an alert
        $this->pushAlert(
            "Expected '$optionName' to be a(n) $expectedType; but current value is: "
            . json_encode($optionValue),
            $alertType
        );

        return false;
    }

    /**
     * Force required options to be set.
     *
     * @return self
     */
    public function forceRequiredOptions(): self
    {
        /*
         * Based on the configuration file and its structure, validate it.
         * See the example for each file in the examples directory to know files' structures.
         */
        switch ($this->getFilename()) {
            case "data.json":
                foreach ($this->validationData as $optionName => $optionData)
                    // If a required value is missing
                    if (!$this->isSet($optionName) && ($optionData->required ?? false) === true)
                        $this->pushError("Required option '$optionName' is missing.");
                break;
            
            case "users.json":
                foreach ($this->iterate() as $i => $userData) {
                    $userName = $this->get("$i.name");
                    $hasName = $userName !== null;
                    $hasMac = !empty($this->get("$i.mac"));

                    // Check the user's data to have a name and at least one MAC address
                    if (!$hasMac || !$hasName) {
                        $this->pushError("User number $i doesn't have " . (!$hasName ? "name" :
                            "any MAC addresses (user's name: '$userName')") . ".");
                    }
                }
        }

        return $this;
    }

    public function setDefaultValues(): self
    {
        /*
         * Based on the configuration file and its structure, validate it.
         * See the example for each file in the examples directory to know files' structures.
         */
        switch ($this->getFilename()) {
            case "data.json":
                foreach ($this->validationData as $optionName => $optionData)
                    $this->set($optionName, $this->get($optionName) ?? $optionData->defaultValue);
                break;
        }

        return $this;
    }

    /**
     * Fix values if it's possible to be fixed.
     * 
     * As an example, if an option that must be a boolean, is set to "false", this method will convert it to false (i.e. convert its type to boolean).
     *
     * @return self
     */
    public function fixPossibleValues(): self
    {
        /*
         * Based on the configuration file and its structure, validate it.
         * See the example for each file in the examples directory to know files' structures.
         */
        switch ($this->getFilename()) {
            case "data.json":
                foreach ($this->validationData as $optionName => $data)
                    $this->fixValue($optionName);
                break;
            
            case "users.json":
                foreach ($this->iterate() as $i => $userData) {
                    // Fix users' names
                    $this->fixValue("$i.name", "alphanumeric");

                    // Convert MAC addresses to an array
                    $this->set("$i.mac", (array)$this->get("$i.mac"));
                    // Fix users' mac addresses, one by one
                    foreach ($this->get("$i.mac") as $j => $userMac)
                        $this->fixValue("$i.mac.$j", "macAddress");
                }
                break;
        }

        return $this;
    }

    /**
     * Fix a value with a valid type, if it's possible.
     *
     * @param string $index The index of the option to be fixed.
     * @param string $validType Valid type for the value. Pass it null to detect automatically.
     * @return self
     */
    public function fixValue(string $index, string $validType = null): self
    {
        // Set the valid data type automatically
        if ($validType === null)
            $validType = $this->validationData->$index->type ?? "string";

        // Get the current value
        $value = $this->get($index);

        // Prevent from fixing nothing!
        if ($value === null)
            return $this;

        switch ($validType) {
            case "boolean":
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case "integer":
                $value = (int)($value);
                break;

            // Remove all non-alphanumeric characters
            case "alphanumeric":
                $value = preg_replace("/[^a-z0-9]/i", "", $value);
                // Prevent the value from getting empty
                if ($value === "")
                    return $this;
                break;

            // Convert a dash-styled MAC address to a colon-styled one
            case "macAddress":
                $value = strtolower(str_replace("-", ":", $value));
                break;
        }

        // Replace the new value
        $this->set($index, $value);

        return $this;
    }

    /**
     * Tells whether an option name is valid or not.
     *
     * @param string $optionName The option name.
     * @return bool
     */
    public function optionExist(string $optionName): bool
    {
        return isset($this->validationData->$optionName);
    }

    /**
     * Do all validation and fixing methods together.
     *
     * @return self
     */
    public function checkEverything(): self
    {
        return $this
            ->validateTypesOfValues()
            ->forceRequiredOptions()
            ->setDefaultValues()
            ->fixPossibleValues();
    }

    /**
     * Adds an alert (i.e. error/warning) to the list of found alerts.
     *
     * @param string $warning Alert message.
     * @param string $alertType Alert type, can be one of the ALERT_TYPE_* constants.
     * @return self
     */
    protected function pushAlert(string $alert, int $alertType = self::ALERT_TYPE_WARNING): self
    {
        if ($alertType === self::ALERT_TYPE_WARNING)
            $this->alerts["warnings"][] = $alert;
        if ($alertType === self::ALERT_TYPE_ERROR)
            $this->alerts["errors"][] = $alert;

        return $this;
    }

    /**
     * Adds a warning to the list of found alerts.
     *
     * @param string $warning Warning message.
     * @return self
     */
    protected function pushWarning(string $warning): self
    {
        return $this->pushAlert($warning, self::ALERT_TYPE_WARNING);
    }

    /**
     * Adds an error to the list of found alerts.
     *
     * @param string $warning Error message.
     * @return self
     */
    protected function pushError(string $error): self
    {
        return $this->pushAlert($error, self::ALERT_TYPE_ERROR);
    }

    /**
     * Output found-during-validation alerts.
     *
     * @param OutputInterface $output
     * @param array $returnType Determines which messages to be returned and how. For keys:
     * "w" means warnings
     * "e" means errors
     * For values:
     * "w" means output them as warnings (i.e. with a "Warning:" prefix)
     * "e" means output them as errors (i.e. with a "Error:" prefix)
     * "" means output them normally (i.e. without any prefixes)
     * null means don't output them 
     * @return self
     */
    public function outputAlerts(OutputInterface $output, array $returnType = [
        "w" => "w",
        "e" => "e"
    ]): self
    {
        // What should be the message prefix, based on values in $returnType
        $alertMessagePrefix = [
            "w" => "Warning: ",
            "e" => "Error: ",
            "" => ""
        ];

        // Output warnings
        if (($returnType["w"] ?? null) !== null)
            foreach ($this->alerts["warnings"] as $warning)
                $output->writeln($alertMessagePrefix[$returnType["w"]] . $warning);
        
        // Output errors
        if (($returnType["e"] ?? null) !== null)
            foreach ($this->alerts["errors"] as $error)
                $output->writeln($alertMessagePrefix[$returnType["e"]] . $error);
        
        return $this;
    }

    /**
     * Returns how many alerts has been found.
     *
     * @return int
     */
    public function getAlertsCount(): int
    {
        return (count($this->alerts["warnings"]) + count($this->alerts["errors"]));
    }

    /**
     * Throws the first error listed in errors, if there are any.
     *
     * @return self
     */
    public function throwFirstError(): self
    {
        $errors = $this->alerts["errors"];
        if (!empty($errors))
            throw new \Exception($this->alerts["errors"][0]);
        
        return $this;
    }
}
