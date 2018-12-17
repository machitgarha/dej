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

$sh->echo("This may take a while.");

// Stop TCPDump first
`screen -X -S tcpdump.dej quit`;

// Send signal to stop sniffer, and wait while for the process to end
$stopFile = "config/stop"; 
touch($stopFile);
while (file_exists($stopFile))
    sleep(1);

// Stop the backup process
`screen -X -S backup.dej quit`;

$sh->echo("Done!");
