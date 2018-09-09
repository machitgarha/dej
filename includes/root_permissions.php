<?php

// Check for root permissions
function root_permissions(bool $quiet = false) {
    if (`which whoami` === null && !$quiet)
        echo "Warning: We cannot detect if root permissions granted or not." .
            "\nPlease make sure you granted, otherwise, files won't run" .
            "\nsuccessfully and you may have problems." . PHP_EOL . PHP_EOL;

    // If root permissions not set
    if (trim(`whoami`) !== "root") {
        if (!$quiet)
            echo "Root permissions needed." . PHP_EOL;
        return false;
    }

    // Either root permissions granted or we cannot detect
    return true;
}

// You should not run this command as root, if running as root, warn user
function should_not_be_root() {
    if (root_permissions(true)) {
        echo "You should not run this command as root. Continue? [Y(es)/n(o)] ";
        $cliInput = fopen("php://stdin", "r");

        // Analyze user input
        $response = strtolower(trim(fgetc($cliInput)));
        fclose($cliInput);

        if ($response === "n")
            exit("Canceled!" . PHP_EOL);
    }
}
