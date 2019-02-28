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
use Dej\Element\DataValidation;
use Dej\Element\ShellOutput;

/**
 * Validates configuration files.
 */
class CheckCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("check")
            ->setDescription("Checks configuration files to be valid.")
        ;
    }

    /**
     * Executes check command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, $output)
    {
        $output->writeln([
            "Preparing...",
            ""
        ]);

        $dataJson = $this->loadJson("data");
        $isThereAnyWarnings = false;
        $i = 0;

        // Check for missing options that is not set
        $validatedData = DataValidation::new($dataJson)->classValidation();
        $isThereAnyWarnings = !empty($validatedData->getWarnings());

        $validatedData->output(true);

        // Validating options' values (e.g. bad MAC address for interface.mac)
        $validatedData = DataValidation::new($dataJson)->typeValidation();
        $isThereAnyWarnings = $isThereAnyWarnings || !empty($validatedData->getWarnings());

        $validatedData->output(true);

        if (!$isThereAnyWarnings)
            $output->writeln("Good!");
    }
}
