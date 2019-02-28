<?php
/**
 * Dej command files.
 * 
 * @author Mohammad Amin Chitgarha <machitgarha@outlook.com>
 * @see https://github.com/MAChitgarha/Dej
 */

namespace Dej\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Dej\Component\ShellOutput;

/**
 * Updates Dej.
 */
class UpdateCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName("update")
            ->setDescription("Updates Dej to the latest update.")
        ;
    }

    /**
     * Executes update command.
     *
     * @param InputInterface $input
     * @param ShellOutput $output
     * @return void
     */
    protected function execute(InputInterface $input, $output)
    {
        $this->getApplication()->find("install")->run(new ArrayInput([
            "--update" => true
        ]), $output);
    }
}
