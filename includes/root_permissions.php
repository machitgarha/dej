<?php

$shell = new Shell();

// Check for root permissions
function rootPermissions(bool $quiet = false) {
    global $shell;
    if (`which whoami` === null && !$quiet)
        $shell->warn("We cannot detect if root permissions granted or not. Please make sure you " .
            "granted, otherwise, files won't run successfully and you may have problems.", 2);

    // If root permissions not set
    if (trim(`whoami`) !== "root") {
        if (!$quiet)
            $shell->error("Root permissions needed.");
        return false;
    }

    // Either root permissions granted or we cannot detect
    return true;
}