<?php

namespace Dej\Exception;

class ParamException extends \Exception
{
    private $params;
    private $isInternal;

    public function __construct(array $params = [], bool $isInternal = false)
    {
        $this->params = $params;
        $this->isInternal = $isInternal;

        parent::__construct(get_class($this));
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

class FileException extends ParamException {}
    class FileLoadingException extends FileException {}
        class FileOpeningException extends FileLoadingException {}
        class FileReadingException extends FileLoadingException {}
        class FileWritingException extends FileLoadingException {}
        class FileExistenceException extends FileLoadingException {}
        class FilePermissionsException extends FileLoadingException {}
    class FileEmptyException extends FileException {}
    class FileNameInvalidException extends FileException {}

class FieldException extends ParamException {}
    class InvalidFieldValueException extends FieldException {}
    class MissingFieldException extends FieldException {}
