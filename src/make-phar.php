<?php

use Dej\Component\ShellOutput;

require_once __DIR__ . "/../vendor/autoload.php";

$output = new ShellOutput();
$section = $output->section();

// Go to the root of the project
chdir(__DIR__ . "/..");

$section->writeln("Preparing...");

$dejPhar = new Phar("dej.phar");

$dejPhar->startBuffering();

$defaultStub = $dejPhar->createDefaultStub("src/console.php");

$directoriesToBeImported = [
    "data",
    "src",
    "vendor",
];
// Replace directories with CLI arguments, if present
if ($argc > 1) {
    array_shift($argv);
    $directoriesToBeImported = $argv;
}

$filesToBeImported = [
    "LICENSE"
];

// Import files in directories that has to be imported
$section->overwrite("Importing directories...");
foreach ($directoriesToBeImported as $dirName) {
    // Check if it's a directory or not
    if (!is_dir($dirName))
        return $section->overwrite("'$dirName' is not a valid directory (i.e. does not exist).");

    $section->writeln("'$dirName'...");
    $recDirIt = new RecursiveDirectoryIterator($dirName, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($recDirIt) as $file)
        $dejPhar->addFile($file->getPathname());
}

// Import single files that has to be imported
$section->overwrite("Importing files...");
foreach ($filesToBeImported as $filePath) {
    $dejPhar->addFile($filePath);
}

// Set default stub
$dejPhar->setStub("#!/usr/bin/env php" . PHP_EOL . $defaultStub);

$section->overwrite("Saving the Phar file...");

// Save changes
$dejPhar->stopBuffering();

$section->overwrite("Granting permissions... ");

// Grant right permissions, if it has been run as root
if (!@chmod("dej.phar", 0755))
    $section->write("failed (maybe you are not root)");

$section->overwrite("Done!");