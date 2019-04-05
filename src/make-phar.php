<?php

use Dej\Component\ShellOutput;

require_once __DIR__ . "/../vendor/autoload.php";

$output = new ShellOutput();

// Go to the root of the project
chdir(__DIR__ . "/..");

$output->writeln("Preparing...");

$dejPhar = new Phar("dej.phar");

$dejPhar->startBuffering();

$defaultStub = $dejPhar->createDefaultStub("src/console.php");

$directoriesToBeImported = [
    "data",
    "src",
    "vendor",
];
$filesToBeImported = [
    "LICENSE"
];

// Import files in directories that has to be imported
$output->writeln("Importing directories...");
foreach ($directoriesToBeImported as $dirName) {
    $output->writeln("'$dirName'...");
    $recDirIt = new RecursiveDirectoryIterator($dirName, RecursiveDirectoryIterator::SKIP_DOTS);
    foreach (new RecursiveIteratorIterator($recDirIt) as $file)
        $dejPhar->addFile($file->getPathname());
}

// Import single files that has to be imported
$output->writeln("Importing files...");
foreach ($filesToBeImported as $filePath) {
    $dejPhar->addFile($filePath);
}

// Set default stub
$dejPhar->setStub("#!/usr/bin/env php" . PHP_EOL . $defaultStub);

$output->writeln("Saving the Phar file...");

// Save changes
$dejPhar->stopBuffering();

$output->write("Granting permissions... ");

// Grant right permissions, if it has been run as root
if (!@chmod("dej.phar", 0755))
    $output->write("failed (maybe you are not root)");
$output->writeln("");

$output->writeln("Done!");