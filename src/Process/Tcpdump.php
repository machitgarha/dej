<?php

require_once "phar://" . __DIR__ . "/../dej.phar/vendor/autoload.php";

use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;
use Dej\Component\ShellOutput;
use Dej\Component\JSONFileValidation;
use Dej\Component\PathData;

$shellOutput = new ShellOutput();

$tcpdumpDataDirPath = PathData::createAndGetTcpdumpDataDirPath();

try {
    // Load configurations and validate it
    $config = (new JSONFileValidation("config"))
        ->checkEverything()
        ->throwFirstError();
} catch (Throwable $e) {
    return $shellOutput->error($e->getMessage());
}

$interfaceName = $config->get("interface.name");

// File path to save
$logsPath = $config->get("logs.path");

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
    // Write Tcpdump data to files
    $cmd = "$tcpdump -i $interfaceName -C 1 -w " . Path::join($tcpdumpDataDirPath, "tcpdump");

    $tcpdumpProcess = Process::fromShellCommandline($cmd);
    $tcpdumpProcess->setTimeout(null)->run(function ($type, $out) {
        echo $out;
    });
}
