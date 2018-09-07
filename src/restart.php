<?php

// Includes
$incPath = "includes";
$filesPath = [
    "root_permissions.php",
    "shell.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

echol("Restarting Dej...");

// If root permissions set, begin for restarting
if (root_permissions())
    echo `./dej stop` . `./dej start`;