<?php

namespace Dej\Exception;

abstract class Exception extends \Exception
{
    private $params;
    private $isInternal;

    public function __construct(array $params = [], bool $isInternal = false)
    {
        $this->params = $params;
        $this->isInternal = $isInternal;

        $className = array_reverse(explode("\\", get_class($this)))[0];
        parent::__construct($className);
    }

    public function getParams()
    {
        return $this->params;
    }

    public function isInternal()
    {
        return $this->isInternal;
    }
}

class FileException extends Exception {}
    class FileLoadingException extends FileException {}
        class FileOpeningException extends FileLoadingException {}
        class FileReadingException extends FileLoadingException {}
        class FileWritingException extends FileLoadingException {}
        class FileExistenceException extends FileLoadingException {}
        class FilePermissionsException extends FileLoadingException {}
    class FileEmptyException extends FileException {}
    class FileNameInvalidException extends FileException {}

class FieldException extends Exception {}
    class InvalidFieldValueException extends FieldException {}
    class MissingFieldException extends FieldException {}
