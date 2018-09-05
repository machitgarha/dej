<?php

// Prints an output with a new line at the end
function echol(string $str, int $newLinesCount) {
    echo $str;
    for ($i = 0; $i < $newLinesCount; $i++)
        echo PHP_EOL;
}

// Prints an output with a new line at the end and exits
function exitl(string $str) {
    exit($str . PHP_EOL);
}