<?php

namespace Dej\Exception;

/**
 * Dej exception.
 */
abstract class Exception extends \Exception
{
    /**
     * Construct a new exception, just like normal exceptions.
     *
     * @param string $message
     * @param integer $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
