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

// Change directory to TCPDump logs path
chdir(forceEndSlash($config->logs->path) . "tcpdump");

// TCPDump executable file
$tcpdump = $config->executables->tcpdump;

$i = 0;
while (true) {
    $tcpdumpFile = "tcpdump" . ($i === 0 ? "" : $i);
    $tcpdumpFileNext = "tcpdump" . ($i + 1);
    $packetsFile = "packets" . $i;
    $packetsDoneFile = "packets-done" . $i;
    
    // Check for the next file to be exist
    $nextFileExists = file_exists($tcpdumpFileNext);

    // Check the next file's size
    $isNextFileBig = $nextFileExists ? (filesize($tcpdumpFileNext) > 50000) : false;

    // Clear file cache to prevent from an endless loop in an $i value
    clearstatcache();

    // Read raw packets if the current file is done, otherwise, wait for it
    if (!($nextFileExists && $isNextFileBig)) {
        sleep(1);
        continue;
    }

    // Create the packets file
    touch($packetsFile);

    /*
    * Read the TCPDump log files and output them to files, using these options:
    * -e: To get MAC address and use them
    * -t: Removing timestamp from the output
    * -r: Read from the file
    * After reading, create a packets-done file to let the sniffer know that it must sniff the read
    * packets saved in the file.
    */
    $readerCmd = "$tcpdump -e -t -r $tcpdumpFile > $packetsFile && touch $packetsDoneFile";

    // Run commands in the background
    `($readerCmd) > /dev/null 2>/dev/null &`; 

    // Sleep to prevent from so much concurrent processes 
    usleep(500 * 1000);

    // Go to the next file
    $i++;
}
