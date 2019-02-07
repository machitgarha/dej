<?php

namespace Dej\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;


class UpdateCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName("update");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->find("install")->run(new ArrayInput([
            "--update" => true
        ]), $output);
    }
}