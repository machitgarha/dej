<?php

// Includes
$incPath = "includes";
$filesPath = [
    "root_permissions.php",
    "screen.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echo "Stopping Dej..." . PHP_EOL;

// Search for Dej screens
$screenSessionPids = search_screens();

// Check if there are some screens to stop
if (count($screenSessionPids) !== 0) {
    // Stop screens that started before
    foreach ($screenSessionPids as $screenSessionPid)
        `screen -X -S $screenSessionPid quit`;
    
    echo "Done!";
} else
    echo "Not started, nothing to do!";

echo PHP_EOL;