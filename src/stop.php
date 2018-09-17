<?php

// Include all include files
require_once "./includes/autoload.php";

echol("Stopping Dej...");

// Stop if root permissions not granted
if (!root_permissions())
    return;

// Search for Dej screens
$screenSessionPids = search_screens();

// Check if there are some screens to stop
if (count($screenSessionPids) !== 0) {
    // Stop screens that started before
    foreach ($screenSessionPids as $screenSessionPid)
        `screen -X -S $screenSessionPid quit`;
    
    echol("Done!");
} else
    echol("Not started, nothing to do!");