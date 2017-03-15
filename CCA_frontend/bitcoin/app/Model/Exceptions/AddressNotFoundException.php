<?php

namespace App\Model\Exceptions;


use Exception;

class AddressNotFoundException extends CCAException
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
