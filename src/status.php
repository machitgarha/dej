<?php

// Includes
$incPath = "includes";
$filesPath = [
    "root_permissions.php",
    "screen.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echo "Checking Dej status..." . PHP_EOL;

// Search for Dej screens
$screenSessionPids = search_screens();
$screensCount = count($screenSessionPids);

switch ($screensCount) {
    case 0:
        echo "Not running.";
        break;

    case 1:
        echo "Warning: Partially running.";
        break;
    
    case 2:
        echo "Running!";
        break;

    default:
        echo "Extra processes running. You should restart Dej.";
        break;
}

echo PHP_EOL;