<?php

class Shell
{
    public $lineLimit;
    public $showStatus;

    private static $messages = [
        "internal_error" => "Interal error occured.",
        "file_reading_error" => "Cannot read %filename%.%+%%?additional_info%",
        "validation_not_found" => "Cannot validate %filename% file with '%validation_type%'" .
            "validation type.",
        "missing_field" => "Missing %?type% '%field%' field in %filename%.",
        "validation_failed" => "Wrong field was set. '%value%' must %conditions%.",
        "warn_bad_type" => "'%field%' field in %filename% is invalid. It must be a(n) %type%. " .
            "Current value: %value%",
        "invalid_input" => "%value% is not a valid %type%."
    ];

    public function __construct(int $lineLimit = 80, bool $showStatus = true)
    {
        // Determines whether to limit all output lines or not
        $this->lineLimit = $lineLimit;

        // Must status be shown before the message?
        $this->showStatus = $showStatus;
    }

    // Output a string with some lines before and after
    public function echo(string $str = "", int $linesAfter = 1, int $linesBefore = 0)
    {
        $output = "";

        // Add lines before
        for ($i = 0; $i < $linesBefore; $i++)
            $output .= PHP_EOL;

        // Add main content
        $output .= $str;

        // Add lines after
        for ($i = 0; $i < $linesAfter; $i++)
            $output .= PHP_EOL;

        // Output with limited lines
        echo $this->limit_lines($output, $this->lineLimit);
    }

    // Warn user about something
    public function warn(string $output, array $bindValues = null, bool $noNewLineAtEnd = false)
    {
        // Bind values, if pointed to in-class messages
        if ($bindValues !== null)
            $output = $this->bind_values($output, $bindValues);

        // Output it
        $this->echo(($this->showStatus ? "Warning: " : "") . $output, $noNewLineAtEnd ? 0 : 1);
    }

    // Outputs an error and exits the program
    public function exit(string $output, array $bindValues = null)
    {
        // Bind values, if pointed to in-class messages
        if ($bindValues !== null)
            $output = $this->bind_values($output, $bindValues);

        $this->echo(($this->showStatus ? "Error: " : "") . $output);
        exit();
    }

    private function bind_values(string $messageIndex, array $bindData): string
    {
        // Preparing to bind values
        $toBind = [];
        foreach ($bindData as $key => $val)
            $toBind["%$key%"] = $val;

        // Preparing output message
        $message = str_replace(array_keys($toBind), array_values($toBind),
            self::$messages[$messageIndex]);
        
        // Replace enters
        $message = str_replace("%+%", PHP_EOL, $message);

        // Skip optional output parameters
        return preg_replace("/\s*%\?.+%/", "", $message);
    }

    // Changing the format of the message not to be more than $lineSize
    public function limit_lines(string $message, int $lineSize = 80): string
    {
        if ($lineSize <= 0)
            return $message;

        // Replace taps with 4 spaces, and explode message by new lines
        $messageParts = explode(PHP_EOL, str_replace("\t", str_repeat(" ", 4), $message));

        $messageLines = [];

        $splitLines = function ($message, $indent) use (&$messageLines, $lineSize) {
            while (true) {
                // Reached the end of message
                if ($message === "")
                    break;
    
                // Remove redundant preceding spaces
                $message = $indent . ltrim($message);
    
                // Extract current line that we are checking
                $currentLine = substr($message, 0, $lineSize);

                // Set cut index If we can cut here exatcly on the $lineSize-th character,
                // or if we reached the end and there is no $linSize-th character.
                if (($message[$lineSize] ?? " ") === " ")
                    $cutIndex = $lineSize;
    
                // If no space or new lines found, find the last space in the current line to cut
                elseif (($lastSpacePos = strrpos($currentLine, " ")) !== false &&
                    $lastSpacePos >= strlen($indent))
                        $cutIndex = $lastSpacePos;
                
                // No whitespaces? We must cut the string
                else
                    $cutIndex = $lineSize;

                // Cut the line from the proper 
                $line = substr($currentLine, 0, $cutIndex);
    
                // Add the line
                $messageLines[] = $line;

                // Remove current string from the messag, and if there is a new line, remove it too
                $message = substr($message, strlen($line));
            }
        };

        // Do for each part that was separated by a new line
        foreach ($messageParts as $messagePart) {
            // Reached the end of message
            if ($messagePart === "") {
                $messageLines[] = "";
                continue;
            }

            // Find spaces count in the current line
            $i = 0;
            for (; ($messagePart[$i] ?? "") === " "; $i++);
            // Don't allow to space more than line size
            $indent = str_repeat(" ", $i % $lineSize);

            $splitLines($messagePart, $indent);
        }

        return implode(PHP_EOL, $messageLines);
    }
}
/*
// Prints an output with a new line at the end
function echol(string $str = "", int $newLinesCount = 1) {
    echo $str;
    for ($i = 0; $i < $newLinesCount; $i++)
        echo PHP_EOL;
}

// Prints an output with a new line at the end and exits
function exitl(string $str) {
    exit($str . PHP_EOL);
}

// Warn user, if needed, exit executing
function warn(string $messageIndex, array $bindValues, string $type = "warn") {
    // Handling error messages
    $errorMessages = [
        "internal_error" => "Interal error occured.",
        "file_reading_error" => "Cannot read %filename%.\n%?additional_info%",
        "validation_not_found" => "Cannot validate %filename% file with '%validation_type%'\n" .
            "validation type.",
        "missing_field" => "Missing %?type% '%field%' field in %filename%.",
        "validation_failed" => "Wrong field was set. '%value%' must:\n%conditions%.",
        "warn_bad_type" => "'%field%' field in %filename% is invalid. It must\n"
            . "be a(n) %type%. Current value: %value%",
        "invalid_input" => "%value% is not a valid %type%.\n"
    ];

    // Preparing to bind values
    $bindArr = $bindValues;
    $bindValues = [];
    foreach ($bindArr as $key => $val)
        if (!empty($val))
            $bindValues["%$key%"] = $val;

    // Preparing output message
    $msg = str_replace(array_keys($bindValues), array_values($bindValues),
        $errorMessages[$messageIndex] ?? $messageIndex);

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
}*/