<?php

// Include all include files
require_once "./includes/autoload.php";

try {
    // Force to grant root permissions
    root_permissions();

    $sh->echo("Preparing to uninstall Dej...");

    // Data path
    $dataPath = __DIR__ . "/../";
    chdir($dataPath);

    // Find where Dej has been installed
    $installationPath = trim(`which dej`);
    if (empty($installationPath))
        $sh->error("Not installed");

    $sh->echo("Uninstalling...");

    // Grant right permissions to be able to remove it
    chmod($installationPath, 0755);

    // Remove the file
    unlink($installationPath);

    $sh->echo("Uninstalled successfully.");
} catch (Throwable $e) {
    $sh->error($e);
}