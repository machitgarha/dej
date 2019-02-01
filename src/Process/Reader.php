<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use Dej\Element\Shell;
use Dej\Element\DataValidation;
use MAChitgarha\Component\JSONFile;
use MAChitgarha\Component\Pusheh;
use Webmozart\PathUtil\Path;

$sh = new Shell();

try {
    // Load configurations and validate it
    $config = (new DataValidation(new JSONFile("config/data.json")))
        ->classValidation()
        ->typeValidation()
        ->return();
} catch (Throwable $e) {
    $sh->error($e);
}

// Change directory to TCPDump logs path
chdir(Path::join($config->get("logs.path"), "tcpdump"));

// TCPDump executable file
$tcpdump = $config->get("executables.tcpdump");

$i = 0;
while (true) {
    // Read raw packets if the current file is done, otherwise, wait for it
    list($processingCurrentFile, $resetIndex) = checkNextFile($i);

    // Set up files
    $tcpdumpFile = "tcpdump" . ($i === 0 ? "" : $i);
    $packetsFile = "packets" . $i;
    $packetsDoneFile = "packets-done" . $i;

    if (!$processingCurrentFile) {
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

    // Sleep to prevent from so many concurrent processes 
    usleep(500 * 1000);

    // Go to the next file
    $i++;

    // Reset index is for when TCPDump process has been restarted
    if ($resetIndex)
        $i = 0;
}

// Checks whether working with current file is done or not
function checkNextFile(int &$index): array {
    // Set up files
    $tcpdumpFile = "tcpdump" . ($index === 0 ? "" : $index);
    $tcpdumpFileNext = "tcpdump" . ($index + 1);

    // Clear cached data about file information
    clearstatcache();

    // Check for the next file to be exist
    $nextFileExists = file_exists($tcpdumpFileNext);
    /*
     * Check if TCPDump process has been restarted or not. For example, if the interface goes down,
     * a new TCPDump process will be invoked.
     */
    if (!$nextFileExists && $tcpdumpFile !== "tcpdump" && file_exists("tcpdump")
        && !file_exists("packets0")) {
        $tcpdumpFileNext = "tcpdump";
        $nextFileExists = true;
    }
    // Check the next file's size
    $isNextFileBig = $nextFileExists ? (filesize($tcpdumpFileNext) > 50000) : false;

    // If TCPDump restarted, reset the index
    $resetIndex = false;
    if ($tcpdumpFileNext === "tcpdump" && $isNextFileBig)
        $resetIndex = true;

    return [
        $isNextFileBig, // Processing the current file?
        $resetIndex
     ];
}
