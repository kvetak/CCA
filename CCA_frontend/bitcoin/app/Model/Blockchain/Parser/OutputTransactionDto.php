<?php
/**
 * Author: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 15:28
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class OutputTransactionDto
 * @package BlockChainParser
 *
 * Data transfer object for output of transaction
 */
class OutputTransactionDto
{
    /**
     * @var Integer - amount of BTC to send
     */
    private $value;

    /**
     * @var VarInt - length of pubkey
     */
    private $scriptPubkeyLen;

    /**
     * @var String - script for output
     */
    private $scriptPubkey;

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return VarInt
     */
    public function getScriptPubkeyLen()
    {
        return $this->scriptPubkeyLen;
    }

    /**
     * @param VarInt $scriptPubkeyLen
     */
    public function setScriptPubkeyLen($scriptPubkeyLen)
    {
        $this->scriptPubkeyLen = $scriptPubkeyLen;
    }

    /**
     * @return String
     */
    public function getScriptPubkey()
    {
        return $this->scriptPubkey;
    }

    /**
     * @param String $scriptPubkey
     */
    public function setScriptPubkey($scriptPubkey)
    {
        $this->scriptPubkey = $scriptPubkey;
    }


    public function to_string()
    {
        $string="value: ".$this->value.PHP_EOL.
            "effective value: ".($this->value* pow(10,-8)).PHP_EOL.
            "script len:".$this->scriptPubkeyLen.PHP_EOL.
            "script: ".bin2hex($this->scriptPubkey).PHP_EOL;
        return $string;
    }
}