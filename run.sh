#!/bin/sh

# PHP executable to run PHP files
PHP_EXECUTABLE="php";

# Run this file to start capturing data usage. Don't try neither to edit
# the following lines nor to move file. If so, you may have problems.

# Check for PHP executable to be exist
HAVE_PHP=`which $PHP_EXECUTABLE`;

# Start capturing if PHP can be ran
if [ "$HAVE_PHP" ]; then
    $PHP_EXECUTABLE -f src/index.php -- $PHP_EXECUTABLE;
else
    echo "You must have php command installed. Simply, install PHP, " +
        "or change the executable file path defined at the beginning " +
        "of 'run.sh'. Then try again.";
fi
