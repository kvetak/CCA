<?php
/**
 * @file: BitcoinTransactionInputDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;


use App\Model\Bitcoin\ScriptParser\Dto\BitcoinScriptInputDto;

class BitcoinTransactionInputDto
{
    /**
     * Identifikuje transakci, ze které se berou BTC pro tento vstup
     * @var int
     */
    private $txid;

    /**
     * V transakci idenitfikuje výstup, který je zde použit jako vstup
     * @var int
     */
    private $vout;

    /**
     * Množství vložených BTC
     * @var double
     */
    private $value;

    /**
     * Text ScriptSig použitého v této transakce
     * @var String
     */
    private $scriptSig;

    /**
     * Parsed script sig
     * @var BitcoinScriptInputDto
     */
    private $parsedScriptSig;

    /**
     * Ze které se brali BTC na tomto vstupu transakce
     * @var string - Serializovaná adresa
     */
    private $serializedAddress;

    /**
     * @return int
     */
    public function getTxid()
    {
        return $this->txid;
    }

    /**
     * @param int $txid
     */
    public function setTxid($txid)
    {
        $this->txid = $txid;
    }

    /**
     * @return int
     */
    public function getVout()
    {
        return $this->vout;
    }

    /**
     * @param int $vout
     */
    public function setVout($vout)
    {
        $this->vout = $vout;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
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
     * @return BitcoinScriptInputDto
     */
    public function getParsedScriptSig()
    {
        return $this->parsedScriptSig;
    }

    /**
     * @param BitcoinScriptInputDto $parsedScriptSig
     */
    public function setParsedScriptSig($parsedScriptSig)
    {
        $this->parsedScriptSig = $parsedScriptSig;
    }

    /**
     * @return string
     */
    public function getSerializedAddress()
    {
        return $this->serializedAddress;
    }

    /**
     * @param string $serializedAddress
     */
    public function setSerializedAddress($serializedAddress)
    {
        $this->serializedAddress = $serializedAddress;
    }
}