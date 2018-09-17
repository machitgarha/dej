<?php

// Get all include files
$includeFiles = new DirectoryIterator(__DIR__);

foreach ($includeFiles as $includeFile)
    if ($includeFile->getFilename() !== "autoload.php" && !$includeFile->isDot())
        require_once($includeFile->getPathname());
