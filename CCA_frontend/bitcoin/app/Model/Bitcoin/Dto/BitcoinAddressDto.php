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
     * Aktuální zůstatek, který je na adrese
     * @var float
     */
    private $balance;

    /**
     * Identifikátory tagů, se kterými se tato adresa váže
     * @var array<int>
     */
    private $tags;

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
}