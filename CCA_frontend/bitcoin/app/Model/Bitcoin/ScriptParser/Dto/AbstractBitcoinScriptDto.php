<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 10.3.17
 * Time: 14:46
 */

namespace App\Model\Bitcoin\ScriptParser\Dto;


abstract class AbstractBitcoinScriptDto
{
    /**
     * Basic transaction pubkey script types
     */
    const PAY_TO_PUBKEY=0,
        PAY_TO_HASH_PUBKEY=1,
        PAY_TO_SCRIPT_HASH=2,
        PAY_TO_MULTISIG=3,
        PAY_NULLDATA=4,
        SCRIPT_UNKNOWN=5;

    /**
     * Type of transaction script
     * @var
     */
    protected $type;


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}