<?php

namespace Dej\Element;

use MAChitgarha\Component\JSONFile;
use Webmozart\PathUtil\Path;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class ShellOutput implements OutputInterface
{
    private $messages;
    public $lineLimit;
    public $showErrorMessage;

    public function __construct(bool $showErrorMessage = false, int $lineLimit = 80)
    {
        try {
            // Error messages to use
            $this->messages = new JSONFile(Path::join(__DIR__, "/../../data/output/errors.json"));
        } catch (Throwable $e) {
            exit("Error: Unknown error." . PHP_EOL);
        }

        // Determines whether to limit all output lines or not
        $this->lineLimit = $lineLimit;

        // Determines whether to show the full output or not
        $this->showErrorMessage = $showErrorMessage;
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
        echo $this->limitLines($output, $this->lineLimit);
    }

    // Warn user about something
    public function warn($error = null, array $options = [])
    {
        $this->prepareOutput("Warning", $error, $options);
    }

    // Outputs an error and exits the program
    public function error($error = null, array $options = [])
    {
        $this->prepareOutput("Error", $error, $options);
        exit();
    }

    public function exit(string $message = null, array $options = [])
    {
        $this->prepareOutput("exit", $message, $options);
        exit();
    }

    private function prepareOutput(string $type, $error, array $options)
    {
        $output = $this->bindValues($error);

        // To put the status in the beginning or not
        $messagePrefix = "";
        if ($type !== "exit")
            $messagePrefix = "$type: ";

        // Handling empty lines after and before
        $linesAfter = $options["linesAfter"] ?? 1;
        $linesBefore = $options["linesBefore"] ?? 0;

        $this->echo($messagePrefix . $output, $linesAfter, $linesBefore);
    }

    private function bindValues($error): string
    {
        if (is_string($error))
            return $error;

        // Return error message, based on showErrorMessage property
        $showErrorMessage = $this->showErrorMessage;
        $getErrorMessage = function (string $errorMessage = null) use ($showErrorMessage) {
            $defaultErrorMsg = "Unknown error.";
            return $showErrorMessage ? ($errorMessage ?? $defaultErrorMsg) : $defaultErrorMsg;
        };

        // If it wasn't an instance of ParamException
        if (!($error instanceof ParamException))
            if ($error instanceof Throwable)
                return $getErrorMessage($error->getMessage());
            else
                return $getErrorMessage();

        // If the message index not exists or it is an internal error, don't bind values
        $messageIndex = $error->getMessage();
        if (!$this->messages->isSet($messageIndex) || $error->isInternal())
            return $getErrorMessage($messageIndex);

        // Preparing to bind values
        $message = $this->messages->get($messageIndex);
        foreach ($error->getParams() as $key => $val)
            $message = str_replace("%$key%", $val, $message);

        // Skip optional output paramleters
        return preg_replace("/\s*%\?[a-z_]+%/i", "", $message);
    }

    // Changing the format of the message not to be more than $lineSize
    public function limitLines(string $message, int $lineSize = 80): string
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
                
                // No white-spaces? We must cut the string
                else
                    $cutIndex = $lineSize;

                // Cut the line from the proper 
                $line = substr($currentLine, 0, $cutIndex);
    
                // Add the line
                $messageLines[] = $line;

                // Remove current string from the message, and if there is a new line, remove it too
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

    public function write($messages, $newLine = false, $options = 0)
    {
        $newLine = $newLine ? 1 : 0;

        if (is_iterable($messages)) {
            foreach ($messages as $message)
                $this->echo($message, $newLine);
        } else {
            $this->echo($messages, $newLine);
        }
    }

    public function writeln($messages, $options = 0)
    {
        $this->write($messages, true, $options);
    }

    public function setVerbosity($level)
    {
        throw new \Exception("Not implemented");
    }

    public function getVerbosity()
    {
        return self::VERBOSITY_NORMAL;
    }

    public function isQuiet()
    {
        return false;
    }

    public function isVerbose()
    {
        return false;
    }

    public function isVeryVerbose()
    {
        return false;
    }

    public function isDebug()
    {
        return false;
    }

    public function setDecorated($decorated)
    {
        throw new \Exception("Not implemented");
    }

    public function isDecorated()
    {
        return false;
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        throw new \Exception("Not implemented");
    }

    public function getFormatter()
    {
        return false;
    }
}
