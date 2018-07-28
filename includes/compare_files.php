<?php

// Replace a broken file with the backup
function compare_files(string $path, string $backupDir) {
    // Get files info
    $files = new DirectoryIterator($path);

    // Perform on all files
    foreach ($files as $file) {
        // Get names and paths
        $filename = $file->getFilename();
        $filePath = "$path/$filename";
        $backupFilePath = "$path/$backupDir/$filename";

        // Check for a broken file, and replace it, if needed
        if (file_exists($backupFilePath) &&
        get_num($backupFilePath) > get_num($filePath)) {
            // Remove the broken file
            unlink($filePath);

            // Replace it with the backup file
            copy($backupFilePath, $filePath);
        }
    }
}

// Remove colons from number
function get_num(string $path) {
    return (int)str_replace(",", "", file_get_contents($path));
}