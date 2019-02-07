<?php

namespace Dej\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MAChitgarha\Component\JSONFile;
use Dej\Element\DataValidation;

class CheckCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName("check");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Loading configuration file...");

        // Load configuration file and also validator
        try {
            $dataJson = new JSONFile("config/data.json");
        } catch (Throwable $e) {
            $output->error($e);
        }

        $output->echo("Loaded successfully.", 2);

        // Check for missing fields
        $output->echo("Checking for missing important fields...");

        $validated = (new DataValidation($dataJson))->classValidation();
        if (empty($validated->getWarnings(true)))
            $output->echo("All important fields have been set!");
        $validated->output(true);

        // Check for bad field values (e.g. bad MAC address for interface.mac)
        $output->echo("Checking for invalid field values...", 1, 1);

        $validated = (new DataValidation($dataJson))->typeValidation();
        if (empty($validated->getWarnings(true)))
            $output->echo("Looks good!");
        $validated->output(true);
    }
}