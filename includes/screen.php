<?php

// Search for Dej screens and return their full session name
function search_screens(): array {
    // List of all screens
    $screens = `screen -ls`;

    // Search for screens
    $matches = [];
    preg_match_all("/[0-9]*\.dej/", $screens, $matches, PREG_PATTERN_ORDER);

    return $matches[0];
}