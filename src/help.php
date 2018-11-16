<?php

// Include all include files
require_once "./includes/autoload.php";

$helpPath = "./data/helps/{$argv[1]}.txt";

if (is_readable($helpPath))
    $sh->echo(file_get_contents($helpPath));
else
    $sh->echo("There is no such command.");