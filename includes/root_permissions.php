<?php

$shell = new Shell();

// Check for root permissions
function root_permissions(bool $quiet = false) {
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

// You should not run this command as root, if running as root, warn user
function should_not_be_root() {
    global $shell;
    if (root_permissions(true)) {
        echo "You should not run as root. Continue? [Y(es)/n(o)] ";
        $cliInput = fopen("php://stdin", "r");

        // Analyze user input
        $response = strtolower(trim(fgetc($cliInput)));
        fclose($cliInput);

        if ($response === "n")
            $shell->exit("Canceled!");
    }
}
