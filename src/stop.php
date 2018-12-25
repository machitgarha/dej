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

// Stop TCPDump and the reader instances
`screen -X -S tcpdump.dej quit`;
`screen -X -S reader.dej quit`;

// Send signal to stop sniffer, and wait for the process to end
$stopFile = "config/stop"; 
touch($stopFile);
while (file_exists($stopFile))
    usleep(200 * 1000);

// Stop the backup process
`screen -X -S backup.dej quit`;

$sh->echo("Done!");
