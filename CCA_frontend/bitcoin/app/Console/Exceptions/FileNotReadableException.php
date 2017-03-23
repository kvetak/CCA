<?php

namespace App\Console\Exceptions;

use Exception;

class FileNotReadableException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}