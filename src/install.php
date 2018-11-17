<?php

// Include all include files
require_once "./includes/autoload.php";

try {
    // Force to grant root permissions
    root_permissions();

    $sh->echo("Preparing...");

    $forceMode = false;
    $updateMode = false;
    if (isset($argv[1])) {
        if (in_array($argv[1], ["--force", "--update"]))
            $forceMode = true;
        if ($argv[1] === "--update")
            $updateMode = true;
    }

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

    // Edit the source line of the Dej file to match with the current path
    $dejFile = new SplFileObject("dej", "r");
    $dejFileContentLines = explode(PHP_EOL, $dejFile->fread($dejFile->getSize()));
    foreach ($dejFileContentLines as $key => $line)
        if ($line === "# SOURCE") {
            $dejFileContentLines[$key + 1] = "cd \"$dataPath\"";
            break;
        }
    
    $sh->echo($updateMode ? "Updating..." : "Installing...");

    // Update repository automatically
    if ($updateMode) {
        ob_start();
        `git pull`;
        if (trim(`git pull`) !== "Already up to date.")
            $sh->warn("Cannot update Git repository.");
        ob_get_clean();
    }

    // Create a temporary command file matching new changes
    $tmpFile = "dej" . time() . ".tmp";
    $newFileContents = implode(PHP_EOL, $dejFileContentLines);
    $dejTmpFile = new SplFileObject($tmpFile, "w");
    $dejTmpFile->fwrite($newFileContents);

    // The temporary file path to install
    $dej = force_end_slash($installPath) . "dej";
    
    // Prevent from overwriting an older version
    $toInstall = !file_exists($dej) || $forceMode;

    // Move the temporary file, if not installed
    if ($toInstall)
        copy($tmpFile, $dej);
    unlink($tmpFile);

    if (!$toInstall)
        $sh->error("Already installed.");

    // Grant right permissions
    chmod($dej, 0755);

    $sh->echo("Completed.");
    if (!$updateMode)
        $sh->echo("Type 'dej help' for more information.");
} catch (Throwable $e) {
    $sh->error($e);
}