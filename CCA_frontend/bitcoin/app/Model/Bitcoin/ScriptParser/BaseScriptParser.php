<?php

namespace App\Model\Bitcoin\ScriptParser;

/**
 * Base class for parsing blockchain script fields
 *
 * Class AbstractScriptParser
 * @package App\Model\Bitcoin
 */
class BaseScriptParser
{
    const OP_MAX_NUMERIC_VALUE=16;

    // "konstanty" pro bytecode jednotlivých operací
    public $OP_PUSH_65B,
        $OP_DUP,
        $OP_HASH160,
        $OP_RETURN,
        $OP_PUSH_NUMBER_1,
        $OP_NUMBER_END,
        $OP_CHECKSIG,
        $OP_CHECKMULTISIG,
        $OP_EQUAL,
        $OP_EQUALVERIFY;

    public $SIGNATURE_HEADER_INDICATOR,
        $INTEGER_INDICATOR,
        $OP_FALSE;

    const SIG_HASH_TYPES=array(
        "01" => "SIGHASH_ALL",
        "02" => "SIGHASH_NONE",
        "03" => "SIGHASH_SINGLE",
        "80" => "SIGHASH_ANYONECANPAY"
    );

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        // bytecode pro jednotlivé operace
        $this->OP_PUSH_65B=chr(0x41);
        $this->OP_DUP=chr(0x76);
        $this->OP_HASH160=chr(0xa9);
        $this->OP_RETURN=chr(0x6a);

        $this->OP_PUSH_NUMBER_1=chr(0x51);
        $this->OP_NUMBER_END=chr(0x60);
        $this->OP_CHECKSIG=chr(0xac);
        $this->OP_CHECKMULTISIG=chr(0xae);
        $this->OP_EQUAL=chr(0x87);
        $this->OP_EQUALVERIFY=chr(0x88);

        $this->SIGNATURE_HEADER_INDICATOR=chr(0x30);
        $this->INTEGER_INDICATOR=chr(0x02);
        $this->OP_FALSE=chr(0x00);
    }
}