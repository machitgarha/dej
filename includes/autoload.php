<?php

// Get all include files
$requirementIncludeFiles = new DirectoryIterator(__DIR__ . "/requirements");
foreach ($requirementIncludeFiles as $includeFile)
    if ($includeFile->isFile())
        require_once($includeFile->getPathname());

$includeFiles = new DirectoryIterator(__DIR__);

foreach ($includeFiles as $includeFile)
    if ($includeFile->isFile() && $includeFile->getFilename() !== "autoload.php")
        require_once($includeFile->getPathname());
