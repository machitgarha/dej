#!/bin/sh

# PHP executable to run PHP files, change this if needed
phpExecutable="php"

# Check for PHP executable to be exist
havePhp=`which $phpExecutable`

# Check
truePhpVersion=`php -r "echo (float)(phpversion()) >= 7;"`
if [ "$truePhpVersion" != "1" ]; then
    echo "Your PHP version must be at least 7."
    exit
fi

# Run PHP files, if PHP is present
if [ -n "$havePhp" ]; then
    if [ "$1" = "start" ]; then
        $phpExecutable -f src/$1.php $phpExecutable
    else
        fileName="$1"
        shift
        $phpExecutable -f src/$fileName.php "$@"
    fi
else
    # Warn user for which PHP not exists
    echo "Cannot run PHP. Simply, install PHP, or change the executable path"
    echo "defined at the beginning of 'src/php_check.sh' file. Then, try again."
    echo "\nPHP executable has been set to: $phpExecutable"
fi
