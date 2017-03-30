<?php

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

    const PAY_METHODS=[
        "0" => "PAY TO PUBLIC KEY (P2PK)",
        "1" => "PAY TO HASH OF PUBLIC KEY (P2PKH)",
        "2" => "PAY TO SCRIPT HASH (P2SH)",
        "3" => "PAY TO MULTISIG",
        "4" => "NULLDATA",
        "5" => "UNKNOWN PAY METHOD"
    ];

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