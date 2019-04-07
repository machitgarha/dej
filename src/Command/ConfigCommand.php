<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\NullOutput;
use Dej\Component\ShellOutput;
use Dej\Exception\OutputException;
use Dej\Exception\InternalException;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @throws OutputException If the option is missing.
     * @throws InternalException If the configuration file contains invalid JSON.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ShellOutput);

        $firstArgument = $input->getArgument("option");
        $value = $input->getArgument("value");

        // Handling 'dej config list' command
        if ($firstArgument === "list") {
            $this->printOptionsList($output);
            return 0;
        }

        // Handling 'dej config ? [option]'
        if ($firstArgument === "?") {
            $this->getOptionDetails($value, $output);
            return 0;
        }

        // Handling 'dej config check'
        if ($firstArgument === "check") {
            $this->getApplication()->find("check")->run(new ArrayInput([]), $output);
            return 0;
        }

        if (empty($firstArgument))
            throw new OutputException("Bad usage.");

        try {
            $dataJson = $this->loadJson("config");
        } catch (\Throwable $e) {
            throw new InternalException("A configuration file could not be loaded.");
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
            return 0;
        }

        // From here, handling 'dej config [option] [value]'
        $option = $firstArgument;

        // If the option is not available in options list
        if (!$dataJson->optionExist($option)) {
            $output->writeln([
                "Invalid option '$option'.",
                "Run 'dej config list' for more info."
            ]);
            return 1;
        }

        // Get option's current value
        $curValue = $dataJson->get($option);

        // Set the new value and check if it's valid or not
        $dataJson->set($option, $value);
        $isValueValid = $dataJson
            ->fixValue($option)
            ->hasValidType($option);

        // Output alerts as errors
        if (!$isValueValid) {
            $dataJson->outputAlerts($output, ["w" => "e", "e" => "e"]);
            return 1;
        }

        // Update the value and save the file
        $dataJson->save();

        $output->write("Done! ");

        // Print the changes, if there were any
        $newValue = $dataJson->get($option);
        if ($curValue !== null && $curValue !== $newValue)
            $output->write("(" . json_encode($curValue) . " => " . json_encode($newValue) . ")");

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
        $warningsCount = $dataJson
            ->checkEverything()
            ->getAlertsCount();

        // If at least a warning found, print it
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
     * @throws InternalException When something goes wrong on fetching options data.
     */
    private function loadConfigOptionsData(): \SimpleXMLElement
    {
        try {
            $file = new \SplFileObject(__DIR__ . "/../../data/helps/config-list.xml", "r");
            $configListXML = $file->fread($file->getSize());
            $file = null;
        } catch (\Throwable $e) {
            throw new InternalException("Cannot fetch the list of options.");
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
