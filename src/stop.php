<?php

// Includes
$incPath = "includes";
$filesPath = [
    "root_permissions.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echo "Stopping Dej..." . PHP_EOL;

// List of all screens
$screens = `screen -ls`;

// Search for proper screens to stop
$matches = [];
$screenSessionPids = [];
preg_match_all("/[0-9]*\.dej/", $screens, $matches, PREG_PATTERN_ORDER);
$screenSessionPids = $matches[0];

// Check if there are some screens to stop
if (count($screenSessionPids) !== 0) {
    // Stop screens that started before
    foreach ($screenSessionPids as $screenSessionPid)
        `screen -X -S $screenSessionPid quit`;
    
    echo "Done!";
} else
    echo "Not started, nothing to do!";

echo PHP_EOL;