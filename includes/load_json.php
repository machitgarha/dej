<?php

function require_json_file(string $filename, string $prefixPath) {
    try {
        return new JSONFile($filename, $prefixPath, JSON::OBJECT_DATA_TYPE, true);
    } catch (FileException $e) {
        warn($e->getMessage(), [], "exit");
    } catch (Throwable $e) {
        echo "Blah!";
        warn("internal_error", [], "exit");
    }
}

function include_json_file(string $filename, string $prefixPath) {
    try {
        return new JSONFile($filename, $prefixPath, JSON::OBJECT_DATA_TYPE, true);
    } catch (FileException $e) {
        warn($e->getMessage());
    } catch (Throwable $e) {
        warn("internal_error");
    }
}