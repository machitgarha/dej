<?php

// Include all include files
require_once "./includes/autoload.php";

// Stop if root permissions not granted
if (!root_permissions())
    return;

// Search for Dej screens
switch (count(search_screens())) {
    case 0:
        $sh->exit("Not running.");

    case 1:
        $sh->warn("Partially running.");
        break;
    
    case 2:
        $sh->exit("Running!");

    default:
        $sh->warn("Running more than once.");
}