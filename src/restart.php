<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Restarting Dej...");

// Restart when permissions granted
if (rootPermissions()) {
    ob_start();
    echo `./dej stop`;
    echo `./dej start`;
    $output = ob_get_clean();

    // Check for errors
    if (preg_match("/(Error)/", $output))
        $sh->error();
    else
        $sh->echo("Done!");
}