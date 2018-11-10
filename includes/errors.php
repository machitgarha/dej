<?php

class FileException extends Exception {}
    class FileLoadingException extends FileException {}
        class FileOpeningException extends FileLoadingException {}
        class FileReadingException extends FileLoadingException {}
        class FileWritingException extends FileLoadingException {}
        class FileExistanceException extends FileLoadingException {}
        class FilePermissionsException extends FileLoadingException {}
    class FileEmptyException extends FileException {}