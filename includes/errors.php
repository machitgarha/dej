<?php

class FileException extends Exception {}
    class FileReadingException extends FileException {}
    class FileWritingException extends FileException {}
    class FileOpeningException extends FileException {}
    class FileExistanceException extends FileException {}
    class FilePermissionsException extends FileException {}