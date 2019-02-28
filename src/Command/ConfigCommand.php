<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Dej\Component\DataValidation;
use MAChitgarha\Component\Pusheh;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Dej\Component\ShellOutput;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Configures Dej.
 * 
 * For more details, see the command's help.
 */
class ConfigCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("config")
            ->addArgument("option", InputArgument::OPTIONAL)
            ->addArgument("value", InputArgument::OPTIONAL)
            ->setDescription("Configures Dej.")
            ->setHelp($this->getHelpFromFile("config"))
        ;
    }

    /**
     * Executes config command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     * @throws \Exception If passing the first argument (i.e. option) is empty.
     * @throws \Exception If the configuration file contains invalid JSON.
     * @throws \RuntimeException When the JSON file contents are invalid.
     */
    protected function execute(InputInterface $input, $output)
    {
        $firstArgument = $input->getArgument("option");
        $value = $input->getArgument("value");

        // Handling 'dej config list' command
        if ($firstArgument === "list") {
            $this->printOptionsList($output);
            return;
        }

        // Handling 'dej config ? [option]'
        if ($firstArgument === "?") {
            $this->getOptionDetails($value, $output);
            return;
        }

        // Handling 'dej config check'
        if ($firstArgument === "check") {
            $this->getApplication()->find("check")->run(new ArrayInput([]), $output);
            return;
        }

        if (empty($firstArgument))
            throw new \Exception("Bad usage.");

        try {
            $dataJson = $this->loadJson("data");
        } catch (\Throwable $e) {
            throw new \Exception("Invalid configuration file detected.");
        }

        // Handling 'dej config [option]'
        if ($value === null) {
            if ($dataJson->isSet($firstArgument)) {
                $output->writeln($dataJson->get($firstArgument));
            } else {
                $output->writeln([
                    "Option '$firstArgument' is not set.",
                    "You can set it by running 'dej config $firstArgument [value]'."
                ]);
            }
            return;
        }

        // From here, handling 'dej config [option] [value]'
        $option = $firstArgument;

        // Load all available options
        $types = $this->loadJson("type", "data/validation")->get("data\.json");
        $availableOptions = [];
        foreach ((array)$types as $key => $val)
            array_push($availableOptions, $key);

        // If the option is not available in options list
        if (!in_array($option, $availableOptions)) {
            $output->writeln([
                "Invalid option '$firstArgument'.",
                "Run 'dej config list' for more info."
            ]);
            return;
        }

        $output->writeln("Updating configurations...");

        // Get option's current value
        $currentValue = $dataJson->get($firstArgument);

        // Fix values
        $type = $types->$option->type ?? "string";
        switch ($type) {
            case "bool":
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case "int":
                $value = filter_var($value, FILTER_VALIDATE_INT);
                break;

            case "alphanumeric":
                $value = preg_replace("/[^a-z0-9]/i", "", $value);
                break;

            /*
             * As a MAC address cannot be fixed, just alert user if it's invalid.
             * Also, convert a dash-styled MAC address to colon-styled one.
             */
            case "mac":
                if (!preg_match("/^([\da-f]{2}[:-]){5}([\da-f]{2})$/i", $value))
                    throw new \RuntimeException("Wrong MAC address was given.");
                
                $value = str_replace("_", ":", $value);
                break;
        }

        // Update the value and save the file
        $dataJson->set($option, $value);
        $dataJson->save();

        $output->write("Done! ");

        // Print the changes, if there were any
        if ($currentValue !== null && $currentValue !== $value)
            $output->write("(" . json_encode($currentValue) . " => " . json_encode($value) . ")");

        $output->writeln([
            "",
            ""
        ]);

        // Restart Dej to see the effects and show the result, if root permissions granted
        try {
            $this->forceRootPermissions(new NullOutput());
            $this->getApplication()->find("restart")->run(new ArrayInput([]), $output);
        } catch (\Throwable $e) {
            $output->warn("You have to restart Dej to see effects.");
        }

        // Checks configuration file for warnings
        $warnings = DataValidation::new($this->loadJson("data"))
            ->classValidation()
            ->typeValidation()
            ->getWarnings();

        // If at least a warning found, print it
        $warningsCount = count($warnings);
        if ($warningsCount !== 0) {
            $output->writeln([
                "",
                "Found $warningsCount warning(s) in the configuration file.",
                "Try 'dej config check' for more details.",
            ]);
        }
    }

    /**
     * Loads configuration options data, as an XML data.
     *
     * @return \SimpleXMLElement Configuration options data.
     * @throws \Exception When something goes wrong on fetching options data.
     */
    private function loadConfigOptionsData(): \SimpleXMLElement
    {
        try {
            $file = new \SplFileObject(__DIR__ . "/../../data/helps/config-list.xml", "r");
            $configListXML = $file->fread($file->getSize());
            $file = null;
        } catch (\Throwable $e) {
            throw new \Exception("Cannot fetch the list of options.");
        }

        return new \SimpleXMLElement($configListXML);
    }

    /**
     * Print a list of available options.
     *
     * List the options based on terminal width. If it's smaller than 60, then the list of options' names will be printed only; otherwise, if the screen is large enough, then the options will be printed with more details (i.e. with descriptions and default values) in a table. The table will also be styled based on the terminal width.
     *
     * @param ShellOutput $output
     * @return void
     */
    private function printOptionsList(ShellOutput $output)
    {
        $configListXML = $this->loadConfigOptionsData();

        $shellWidth = ShellOutput::getShellWidth();
        if ($shellWidth < 60) {
            $output->warn("Your terminal is too small. Available options:");
            foreach ($configListXML->option as $option)
                $output->writeln(" " . $option->name);
        } else {
            // 40: The approximate width of the names and default values columns
            $descriptionWidth = $shellWidth - 40;

            $tableRows = [];
            foreach ($configListXML->option as $option) {
                $tableRows[] = [
                    $option->name,
                    ShellOutput::limitLines(trim($option->description), $descriptionWidth),
                    $option->default ?? "",
                ];
            }

            // Disable line limit, because limiting table is not a good idea
            $output->disableLineLimit();

            // Create the table
            $table = new Table($output);
            $table->setHeaders([
                "Name",
                "Description",
                "Default"
            ]);
            $table->setRows($tableRows);
            $table->setStyle("box")->render();

            $output->enableLineLimit();
        }

        $output->writeln("For more details on each option, try 'dej config ? [option]'.");
    }

    /**
     * Get detailed information about a configuration option.
     *
     * @param string $optionName The name of the option to fetch.
     * @param ShellOutput $output
     * @return void
     */
    private function getOptionDetails(string $optionName, ShellOutput $output)
    {
        $configListXML = $this->loadConfigOptionsData();

        foreach ($configListXML->option as $option) {
            if ($option->name[0] == $optionName) {
                $output->writeln([
                    "Name: {$option->name}",
                    "Description: " . trim($option->description),
                    "Default value: " . ($option->default[0] ?? "None")
                ]);
                return;
            }
        }

        $output->writeln("'$optionName' option not found.");
    }
}
