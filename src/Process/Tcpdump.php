<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use Dej\Element\DataValidation;
use MAChitgarha\Component\JSONFile;
use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use Dej\Element\Shell;
use Symfony\Component\Process\Process;
use Dej\Element\ShellOutput;

$sh = new ShellOutput();

try {
    // Load configurations and validate it
    $config = (new DataValidation(new JSONFile("config/data.json")))
        ->classValidation()
        ->typeValidation()
        ->return();
} catch (Throwable $e) {
    $sh->error($e);
}

$interfaceName = $config->get("interface.name");

// File path to save
$logsPath = $config->get("logs.path");
Pusheh::createDirRecursive(Path::join($logsPath, "tcpdump"));

// TCPDump executable file
$tcpdump = $config->get("executables.tcpdump");

/*
* Use TCPDump to sniff network packets. Official TCPDump site: www.tcpdump.org
* -i: Sniffing the selected interface
* -w: Write to the file
* -C: The size of each TCPDump log file
* Use the loop for when the device is not set up
*/
while (true) {
    $tcpdumpLogsPath = Path::join($logsPath, "tcpdump", "tcpdump");
    $cmd = "$tcpdump -i $interfaceName -C 1 -w $tcpdumpLogsPath";
    $tcpdumpProcess = Process::fromShellCommandline($cmd);
    $tcpdumpProcess->setTimeout(null)->run(function ($type, $out) {
        echo $out;
    });
}
