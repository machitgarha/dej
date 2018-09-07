<?php

// Check for root permissions
function root_permissions() {
    if (`which whoami` === null)
        echo "Warning: We cannot detect if root permissions granted or not." .
            "\nPlease make sure you granted, otherwise, files won't run" .
            "\nsuccessfully and you may have problems." . PHP_EOL . PHP_EOL;

    // If root permissions not set
    if (trim(`whoami`) !== "root") {
        echo "Root permissions needed." . PHP_EOL;
        return false;
    }

    // Either root permissions granted or we cannot detect
    return true;
}
