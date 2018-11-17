<?php

// Include all include files
require_once "./includes/autoload.php";

$command = $argv[1];
$helpPath = "./data/helps/$command.txt";

if (is_readable($helpPath))
    $sh->echo(file_get_contents($helpPath));
else {
    $sh->echo("Unknown command '$command'.");
    $sh->echo("Try 'dej help' for more information.");
}