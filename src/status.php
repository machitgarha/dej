<?php

// Include all include files
require_once "./includes/autoload.php";

// Stop if root permissions not granted
if (!rootPermissions())
    return;

// Search for Dej screens
switch (count(searchScreens())) {
    case 0:
        $sh->exit("Not running.");

    case 1:
    case 2:
        $sh->warn("Partially running.");
        break;
    
    case 3:
        $sh->exit("Running!");

    default:
        $sh->warn("Running more than once.");
}