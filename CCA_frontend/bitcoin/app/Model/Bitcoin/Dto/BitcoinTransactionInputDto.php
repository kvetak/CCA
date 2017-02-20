<?php
/**
 * @file: BitcoinTransactionInputDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;


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
     * Pole adres, použitých pro tento vstup
     * @var array
     */
    private $addresses;



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
     * @return array
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param array $addresses
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }
}