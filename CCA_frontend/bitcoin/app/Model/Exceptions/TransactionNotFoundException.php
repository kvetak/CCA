<?php
/**
 * @file: TransactionNotFoundException.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Exceptions;


use Exception;

class TransactionNotFoundException extends CCAException
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}