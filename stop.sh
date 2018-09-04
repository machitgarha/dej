#!/bin/sh

# Check for root permissions
if [ `whoami` = "root" ]; then
    echo "Stopping everything... ";

    # Close all screens related to this project
    for session in `screen -ls | grep -o '[0-9]*\.dej'`; do
        screen -S "${session}" -X quit;
    done

    echo "Done!";
else
    echo "Root permissions needed.";   
fi