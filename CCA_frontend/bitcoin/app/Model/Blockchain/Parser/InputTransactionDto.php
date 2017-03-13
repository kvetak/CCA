<?php
/**
 * Author: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 15:25
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class InputTransactionDto
 * @package BlockChainParser
 *
 * Data transfer object for information about input in transaction
 */
class InputTransactionDto
{
    /**
     * Hash transakce ze které pocházejí BTC použité na tomto vstupu
     * @var Hash - hash of past transaction
     */
    private $hash;

    /**
     * @var Integer - index of output, in transaction specified by $hash
     */
    private $index;

    /**
     * @var VarInt - Length of script sig
     */
    private $scriptSigLen;

    /**
     * @var String - script field
     */
    private $scriptSig;


    /**
     * @var Integer - transaction input sequence number
     */
    private $sequence;

    /**
     * @return Hash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param Hash $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return VarInt
     */
    public function getScriptSigLen()
    {
        return $this->scriptSigLen;
    }

    /**
     * @param VarInt $scriptSigLen
     */
    public function setScriptSigLen($scriptSigLen)
    {
        $this->scriptSigLen = $scriptSigLen;
    }

    /**
     * @return String
     */
    public function getScriptSig()
    {
        return $this->scriptSig;
    }

    /**
     * @param String $scriptSig
     */
    public function setScriptSig($scriptSig)
    {
        $this->scriptSig = $scriptSig;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @param int $sequence
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    }


    public function to_string()
    {
        $string="hash: ".$this->hash.PHP_EOL.
            "index (previous output): ".dechex($this->index).PHP_EOL.
            "script_len: ".$this->scriptSigLen.PHP_EOL.
            "script: ".bin2hex($this->scriptSig).PHP_EOL.
            "sequence: ".$this->sequence.PHP_EOL;
        return $string;
    }
}