<?php

// Include all include files
require_once "./includes/autoload.php";

// Force to grant root permissions
root_permissions();

$sh->echo("Preparing to install Dej...");

// Data path
$dataPath = __DIR__ . "/../";
chdir($dataPath);

// Extract $PATH info to install there
$defaultInstallPath = "/usr/local/bin";
$paths = explode(":", `echo \$PATH`);
if (in_array($defaultInstallPath, $paths))
    $installPath = $defaultInstallPath;
else
    $installPath = $paths[0];

$sh->echo("Preparing command file...");

// Edit the source line of the Dej file to match with the current path
$dejFileContentLines = explode(PHP_EOL, file_get_contents("dej"));
foreach ($dejFileContentLines as $key => $line)
    if ($line === "# SOURCE") {
        $dejFileContentLines[$key + 1] = "cd \"$dataPath\"";
        break;
    }

// Create a temporary command file matching new changes 
$tmpFilename = "dej.tmp";
$newFileContents = implode(PHP_EOL, $dejFileContentLines);
$dejTmpFile = new SplFileObject($tmpFilename, "w");
$dejTmpFile->fwrite($newFileContents);
`sleep 1`;

$sh->echo("Installing...");

// Move the temporary file to install path
$dejInstalledFile = force_end_slash($installPath) . "dej";
copy($tmpFilename, $dejInstalledFile);
unlink($tmpFilename);

// Grant right permissions
chmod($dejInstalledFile, 0755);