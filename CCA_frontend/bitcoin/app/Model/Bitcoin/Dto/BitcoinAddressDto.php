<?php
/**
 * @file: BitcoinAddressDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;


class BitcoinAddressDto
{
    /**
     * Jedna bitcoin adresa
     * @var string
     */
    private $address;

    /**
     * Veřejný klíč, který dopovídá této adrese
     * Používá se pouze, pokud k dané adrese existuje veřejný klíč a je znám
     *
     * @var string
     */
    private $pubkey;

    /**
     * Identifikátor clusteru, ve kterém je tato adresa
     * @var int
     */
    private $cluster_id;

    /**
     * Aktuální zůstatek, který je na adrese
     * @var float
     */
    private $balance;

    /**
     * Identifikátory tagů, se kterými se tato adresa váže
     * @var array<int>
     */
    private $tags;


    /**
     * Identifikátory transakcí, ve kterých tato adresa figuruje
     * @var array<txid>
     */
    private $transactions;


    public function __construct()
    {
        $this->tags=array();
        $this->transactions=array();
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getPubkey()
    {
        return $this->pubkey;
    }

    /**
     * @param string $pubkey
     */
    public function setPubkey($pubkey)
    {
        $this->pubkey = $pubkey;
    }

    /**
     * @return int
     */
    public function getClusterId()
    {
        return $this->cluster_id;
    }

    /**
     * @param int $cluster_id
     */
    public function setClusterId($cluster_id)
    {
        $this->cluster_id = $cluster_id;
    }

    /**
     * Přičte hodnotu k aktuálnímu zůstatku
     * Zadaná hodnota může být záporná
     *
     * @param float $balanceChange
     */
    public function addBalance($balanceChange)
    {
        $this->balance+=$balanceChange;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Přidá do seznamu transakcí novou
     * @param $txix string - txid transakce
     */
    public function addTransaction($txid)
    {
        $this->transactions[]=$txid;
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array $transactions
     */
    public function setTransactions(array $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return int
     */
    public function getTransactionsCount()
    {
        return count($this->transactions);
    }
}