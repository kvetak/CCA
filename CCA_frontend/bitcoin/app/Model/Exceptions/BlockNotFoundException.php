<?php
/**
 * @file: BlockNotFoundException.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Exceptions;


class BlockNotFoundException extends CCAException
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}