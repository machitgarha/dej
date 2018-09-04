<?php

// Check for root permissions
if (`which whoami` === null)
    echo "Warning: We cannot detect if root permissions provided or not." .
        "\nPlease make sure you provided it, otherwise, files won't run" .
        "\nsuccessfully and you may have problems." . PHP_EOL;
elseif (trim(`whoami`) !== "root")
    exit("Root permissions needed." . PHP_EOL);
