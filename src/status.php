<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Checking Dej status...");

// Stop if root permissions not granted
if (!root_permissions())
    return;

// Search for Dej screens
$screenSessionPids = search_screens();
$screensCount = count($screenSessionPids);

switch ($screensCount) {
    case 0:
        $sh->exit("Not running.");

    case 1:
        $sh->warn("Partially running.");
    
    case 2:
        $sh->exit("Running!");

    default:
        $sh->warn("Running more than once.");
}