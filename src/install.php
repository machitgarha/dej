<?php

// Include all include files
require_once "./includes/autoload.php";

try {
    // Force to grant root permissions
    root_permissions();

    $sh->echo("Preparing to install Dej...");

    // Data path
    $dataPath = __DIR__ . "/../";
    chdir($dataPath);

    // Extract $PATH info and set installation path
    $defaultInstallPath = "/usr/local/bin";
    $paths = explode(":", `echo \$PATH`);
    // Break if install path cannot be specified
    if (empty($paths))
        throw new Exception("Unknown installation path.");
    $installPath = $paths[0];
    if (in_array($defaultInstallPath, $paths))
        $installPath = $defaultInstallPath;

    $sh->echo("Preparing command file...");

    // Edit the source line of the Dej file to match with the current path
    $dejFile = new SplFileObject("dej", "r");
    $dejFileContentLines = explode(PHP_EOL, $dejFile->fread($dejFile->getSize()));
    foreach ($dejFileContentLines as $key => $line)
        if ($line === "# SOURCE") {
            $dejFileContentLines[$key + 1] = "cd \"$dataPath\"";
            break;
        }

    // Create a temporary command file matching new changes
    $tmpFile = "dej" . time() . ".tmp";
    $newFileContents = implode(PHP_EOL, $dejFileContentLines);
    $dejTmpFile = new SplFileObject($tmpFile, "w");
    $dejTmpFile->fwrite($newFileContents);

    $sh->echo("Installing...");

    // The temporary file path to install
    $dejInstallationFilePath = force_end_slash($installPath) . "dej";
    
    // Prevent from overwriting an older version
    $isInstalled = file_exists($dejInstallationFilePath);

    // Move the temporary file, if not installed
    if (!$isInstalled)
        copy($tmpFile, $dejInstallationFilePath);
    unlink($tmpFile);

    if ($isInstalled)
        $sh->error("Already installed.");

    // Grant right permissions
    chmod($dejInstalledFile, 0755);

    $sh->echo("Completed. Type 'dej help' for more information.");
} catch (Throwable $e) {
    $sh->error($e);
}