<?php

// Include all include files
require_once "./includes/autoload.php";

echol("Restarting Dej...");

// If root permissions set, begin for restarting
if (root_permissions())
    echo `./dej stop` . `./dej start`;