<?php

/**
 * Phan static analysis configuration file.
 * @see https://github.com/phan/phan/blob/master/.phan/config.php
 */

return [
    "directory_list" => [
        "src/",
        "vendor/"
    ],
    "exclude_analysis_directory_list" => [
        "vendor/",
    ],
    "exclude_file_list" => [
        "src/Component/Application.php",
    ],
];
