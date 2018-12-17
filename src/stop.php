<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Stopping Dej...");

// Stop if root permissions not granted
rootPermissions();

// Search for Dej screens
$screenSessionPids = searchScreens();

// Check if there are some screens to stop
if (count($screenSessionPids) === 0)
    $sh->exit("Not running.");
    
// Stop TCPDump first
`screen -X -S tcpdump.dej quit`;

// Stop the sniffer, and let it do the last step
try {
    $shmStop = new SharedMemory(0x019, 1);
    $shmStop->write(1);
} catch (Throwable $e) {
    $sh->error($e);
}
while ($shmStop->read() == 1)
    sleep(1);

// Stop the backup process
`screen -X -S backup.dej quit`;

$sh->echo("Done!");
