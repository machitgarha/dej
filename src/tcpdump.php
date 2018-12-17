<?php

// Include all include files
require_once "./includes/autoload.php";

try {
    // Load configurations and validate it
    $config = (new DataValidation(new JSONFile("data.json", "config")))
        ->classValidation()
        ->typeValidation()
        ->returnData();
} catch (Throwable $e) {
    $sh->error($e);
}

$interfaceName = $config->interface->name;

// File path to save
$logsPath = forceEndSlash($config->logs->path);
directory($logsPath);
$tcpdumpLog = $logsPath . $config->logs->tcpdump;

// TCPDump executable file
$tcpdump = $config->executables->tcpdump;

/*
* Uses TCPDump to sniff network packets. Official TCPDump site: www.tcpdump.org
* -i: Sniffing the selected interface
* -w: Write to the file
* Use the loop for when the device is not set up
*/
while (true)
    `$tcpdump -i $interfaceName -C 1 -w $tcpdumpLog`;
