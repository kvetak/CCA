<?php
/**
 * @file: BitcoinTransactionOutputDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;


class BitcoinTransactionOutputDto
{
    const SATOSHIS_COEFICIENT=0.00000001;

    /**
     * Adresy na které směřuje tento výstup
     * @var array
     */
    private $addresses;

    /**
     * Množství BTC utracených v tomto výstupu
     * @var double
     */
    private $value;

    /**
     * Číslo (index) výstupu transakce
     * @var int
     */
    private $n;

    /**
     * Je tento výstup použitý
     * @var bool
     */
    private $spent;

    /**
     * Transakce ve které je tento výstup použit
     * @var int
     */
    private $spentTxid;

    /**
     * Čas použití tohoto výstupu
     * @var int - unix timestamp
     */
    private $spentTs;

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

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Hodnota value je vložena v Satoshis kódování (nativní pro blockchain) a je třeba ji zformátovat
     * @param float $value
     */
    public function setRawValue($value)
    {
        $this->value = $value * self::SATOSHIS_COEFICIENT;
    }

    /**
     * Hodnota value je již zformátovaná a není nutné ji převádět
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value=$value;
    }

    /**
     * @return int
     */
    public function getN()
    {
        return $this->n;
    }

    /**
     * @param int $n
     */
    public function setN($n)
    {
        $this->n = $n;
    }

    /**
     * @return bool
     */
    public function isSpent()
    {
        return $this->spent;
    }

    /**
     * @param bool $spent
     */
    public function setSpent($spent)
    {
        $this->spent = $spent;
    }

    /**
     * @return int
     */
    public function getSpentTxid()
    {
        return $this->spentTxid;
    }

    /**
     * @param int $spentTxid
     */
    public function setSpentTxid($spentTxid)
    {
        $this->spentTxid = $spentTxid;
    }

    /**
     * @return int
     */
    public function getSpentTs()
    {
        return $this->spentTs;
    }

    /**
     * @param int $spentTs
     */
    public function setSpentTs($spentTs)
    {
        $this->spentTs = $spentTs;
    }
}