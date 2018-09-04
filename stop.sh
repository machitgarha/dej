#!/bin/sh

# Close all screens related to this project
for session in $(screen -ls | grep -o '[0-9]*\.dej'); do
    screen -S "${session}" -X quit;
done