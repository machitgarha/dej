<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Dej\Element\DataValidation;
use MAChitgarha\Component\Pusheh;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Dej\Element\ShellOutput;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\NullOutput;

class ConfigCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("config")
            ->addArgument("index", InputArgument::OPTIONAL)
            ->addArgument("value", InputArgument::OPTIONAL)
            ->setDescription("Configures Dej.")
            ->setHelp($this->getHelpFromFile("config"))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $index = $input->getArgument("index");
        $value = $input->getArgument("value");

        if ($index === "list") {
            $this->printOptionsList($output);
            return;
        }

        if ($index === "?") {
            $this->getOptionDetails($value, $output);
            return;
        }

        if ($index === "check") {
            $this->getApplication()->find("check")->run(new ArrayInput([]), $output);
            return;
        }

        if (empty($index))
            throw new \Exception("Bad usage.");

        // Load configurations
        $loaded = true;
        try {
            $dataJson = $this->loadJson("data");
        // If file doesn't exist, attempt to create it
        } catch (\Throwable $e) {
            // Create the configuration file
            try {
                $this->createConfigFile($output);
            } catch (\Throwable $e) {
                $output->writeln($e->getMessage());
            }

            // Load it
            $dataJson = $this->loadJson("data");
        }

        if ($value === null) {
            if ($dataJson->isSet($index)) {
                $output->writeln($dataJson->get($index));
            } else {
                $output->writeln([
                    "Option '$index' does not exist.",
                    "Run 'dej config list' for more info."
                ]);
            }
            return;
        }

        // Load all possible options
        $types = $this->loadJson("type", "data/validation")->get("data\.json");

        // Extract all possible options
        $possibleOptions = [];
        foreach ((array)$types as $key => $val)
            array_push($possibleOptions, $key);

        // Break if it is an invalid option
        if (!in_array($index, $possibleOptions)) {
            $output->writeln([
                "There is no '$index' option exists.",
                "Run 'dej config list' for more info."
            ]);
            return;
        }

        $output->writeln("Updating configurations...");

        // Get field's current value
        $currentValue = $dataJson->get($index);

        // Fix values
        $type = $types->$index->type ?? "string";
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
            
            case "mac":
                if (!preg_match("/^([\da-f]{2}:){5}([\da-f]{2})$/i", $value))
                    $output->error("Wrong MAC address was given.");
                break;
        }

        // Change field's value
        $dataJson->set($index, $value);

        // Open the file to save
        $dataJson->save();

        $output->writeln("Done!");
        if ($currentValue !== null && $currentValue !== $value)
            $output->writeln(json_encode($currentValue) . " -> " . json_encode($value));

        // Restart Dej to see the effects and show the result, if root permissions granted
        try {
            $this->forceRootPermissions(new NullOutput());
            $output->writeln("");
            $this->getApplication()->find("restart")->run(new ArrayInput([]), $output);
        } catch (\Throwable $e) {
            $output->warn("You have to restart Dej to see effects.");
        }

        // Check for warnings
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

    private function createConfigFile(OutputInterface $output)
    {
        $output->writeln("Creating...");

        // Create directory if it does not exist
        Pusheh::createDir("config");
        $dataJsonFile = "config/data.json";

        if (file_exists($dataJsonFile))
            throw new \Exception("Configuration file exists.");

        // Create the configuration
        touch($dataJsonFile);

        // Make right permissions
        chmod($dataJsonFile, 0755);

        $output->echo("Done!");
    }

    private function printOptionsList(ShellOutput $output)
    {
        $configListFile = __DIR__ . "/../../data/helps/config-list.xml";
        $configListXML = new \SimpleXMLElement(file_get_contents($configListFile));

        $shellWidth = ShellOutput::getShellWidth();
        if ($shellWidth < 60) {
            $output->warn("Your terminal is too small. Available options:");
            foreach ($configListXML->option as $option)
                $output->writeln($option->index);
        } else {
            $descriptionWidth = $shellWidth - 40;

            $rows = [];
            foreach ($configListXML->option as $option) {
                $rows[] = [
                    $option->index,
                    ShellOutput::limitLines(trim($option->description), $descriptionWidth),
                    $option->default ?? "",
                ];
            }

            $output->disableLineLimit();
            $table = new Table($output);
            $table->setHeaders([
                "Name",
                "Description",
                "Default"
            ]);
            $table->setRows($rows);
            $table->setStyle("box")->render();
            $output->enableLineLimit();
        }

        $output->writeln("For more details on each option, try 'dej config ? [option]'.");
    }

    private function getOptionDetails(string $optionName, ShellOutput $output)
    {
        $configListFile = __DIR__ . "/../../data/helps/config-list.xml";
        $configListXML = new \SimpleXMLElement(file_get_contents($configListFile));

        foreach ($configListXML->option as $option) {
            if ($option->index[0] == $optionName) {
                $output->writeln([
                    "Name: {$option->index}",
                    "Description: " . trim($option->description),
                    "Default value: " . ($option->default[0] ?? "None")
                ]);
                return;
            }
        }

        $output->writeln("'$optionName' option not found.");
    }
}
