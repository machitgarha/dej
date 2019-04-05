<?php
/**
 * Dej component file.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Component;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Print messages to the shell output.
 */
class ShellOutput extends ConsoleOutput
{
    /** @var bool Whether to limit lines on printing or not. */
    private $toLimitLines = true;
    /** @var int */
    private $lineLimit;

    /**
     * Constructs a shell output.
     *
     * @param integer $lineLimit Line limit (in characters). Pass 0 to disable line limit.
     * Default is shell width.
     * @param integer $verbosity
     * @param boolean $decorated
     * @param OutputFormatterInterface $formatter
     */
    public function __construct(int $lineLimit = null, int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = null, OutputFormatterInterface $formatter = null)
    {
        $this->resetLineLimit($lineLimit);

        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * {@inheritDoc}
     */
    public function doWrite($message, $newLine = false): void
    {
        $output = $message . ($newLine ? PHP_EOL : "");

        // Output with limited lines
        echo ($this->toLimitLines ? self::limitLines($output, $this->lineLimit) : $output);
    }

    /**
     * Output a warning.
     *
     * @param string $message The message.
     * @return self
     */
    public function warn(string $message): self
    {
        $this->writeln("Warning: $message");
        return $this;
    }

    /**
     * Outputs an error.
     *
     * @param string $message The message.
     * @param int $returnCode The return code.
     * @return int The passed return code.
     */
    public function error(string $message, int $returnCode = 1): int
    {
        $this->writeln("Error: $message");
        return $returnCode;
    }

    /**
     * Limit lines of a message.
     * 
     * Explode the message into some lines, based on the given line size.
     *
     * @param string $message The message to be exploded.
     * @param integer $lineSize The line size (line limit).
     * @return string The message extracted into some lines.
     */
    public static function limitLines(string $message, int $lineSize = 80): string
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

    /**
     * Disable limiting lines.
     *
     * @return self
     */
    public function disableLineLimit(): self
    {
        $this->toLimitLines = false;
        return $this;
    }

    /**
     * Enable limiting lines.
     *
     * @return self
     */
    public function enableLineLimit(): self
    {
        $this->toLimitLines = true;
        return $this;
    }

    /**
     * Reset line limit to a new one.
     *
     * @param int $lineLimit Line limit (in characters). Pass 0 to disable line limit.
     * Default is shell width.
     * @return self
     */
    public function resetLineLimit(int $lineLimit = null): self
    {
        if ($lineLimit < 0)
            throw new \InvalidArgumentException("Line limit cannot be negative.");

        if ($lineLimit === 0)
            $this->disableLineLimit();

        // Default line limit is shell width 
        if ($lineLimit === null)
            $lineLimit = self::getShellWidth();

        $this->lineLimit = $lineLimit;

        return $this;
    } 

    /**
     * Gives the width of current shell.
     *
     * It is supported only on Linux systems.
     *
     * @return int
     */
    public static function getShellWidth(): int
    {
        return (int)(`tput cols`);
    }

    /**
     * Gives the height of current shell.
     * 
     * It is supported only on Linux systems.
     *
     * @return int
     */
    public static function getShellHeight(): int
    {
        return (int)(`tput lines`);
    }
}
