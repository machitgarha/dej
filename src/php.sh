#!/bin/sh

# PHP executable to run PHP files, change this if needed
phpExecutable="php"

# Check for PHP executable to be exist
havePhp=`which $phpExecutable`

# Run PHP files, if PHP is present
if [ -n "$havePhp" ]; then
    # Exit if the PHP version is lower than 7
    if [ `$phpExecutable -r "echo (float)(phpversion()) <= 7;"` ]; then
        echo "Error: Your PHP version must be at least 7."
        exit
    fi

    # Run the main part
    if [ "$1" = "start" ]; then
        $phpExecutable src/$1.php $phpExecutable
    else
        fileName="$1"
        shift
        $phpExecutable src/$fileName.php "$@"
    fi
# Warn user for which PHP cannot be found
else
    echo "Cannot run PHP. Simply, install PHP, or change the executable path"
    echo "defined at the beginning of 'src/php.sh' file. Then, try again."
    echo "\nPHP executable has been set to: $phpExecutable"
fi
