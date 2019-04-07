<?php

if ($argc < 2) {
    exit("Too few arguments.");
}

require_once __DIR__ . "/../../vendor/autoload.php";

use Symfony\Component\Process\Process;
use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;
use Dej\Component\ShellOutput;
use Dej\Component\JSONFileValidation;

$shellOutput = new ShellOutput();

$dataConfigPath = $argv[1];

try {
    // Load configurations and validate it
    $config = (new JSONFileValidation($dataConfigPath))
        ->checkEverything()
        ->throwFirstError();
} catch (Throwable $e) {
    return $shellOutput->error($e->getMessage());
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
