#!/bin/sh

# PHP executable to run PHP files, change this if needed
phpExecutable="php"


# Check for PHP executable to be exist
havePhp=`which $phpExecutable`

# Run PHP files, if PHP is present
if [ -n "$havePhp" ]; then
    if [ $# -eq 3 ]; then
        $phpExecutable -f src/$1.php $2 $3
    else
        $phpExecutable -f src/$1.php $phpExecutable
    fi
else
    # Warn user for which PHP not exists
    echo "Cannot run PHP. Simply, install PHP, or change the executable path"
    echo "defined at the beginning of 'src/php_check.sh' file. Then, try again."
    echo "\nPHP executable has been set to: $phpExecutable"
fi
