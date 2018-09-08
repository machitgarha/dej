<?php

// Includes
$incPath = "includes";
$filesPath = [
    "root_permissions.php",
    "screen.php",
    "shell.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echol("Checking Dej status...");

// Stop if root permissions not granted
if (!root_permissions())
    return;

// Search for Dej screens
$screenSessionPids = search_screens();
$screensCount = count($screenSessionPids);

switch ($screensCount) {
    case 0:
        exitl("Not running.");

    case 1:
        exitl("W: Partially running.");
    
    case 2:
        exitl("Running!");

    default:
        exitl("W: Running more than once.");
}