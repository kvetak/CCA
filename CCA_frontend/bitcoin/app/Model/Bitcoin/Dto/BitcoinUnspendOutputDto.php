<?php

namespace App\Model\Bitcoin\Dto;


class BitcoinUnspendOutputDto
{
    /**
     * @var string
     */
    private $transaction_txid;

    /**
     * @var string
     */
    private $address;

    /**
     * @var double
     */
    private $value;

    /**
     * BitcoinUnspendOutputDto constructor.
     * @param string $transaction_txid
     * @param string $address
     * @param float $value
     */
    public function __construct($transaction_txid, $address, $value)
    {
        $this->transaction_txid = $transaction_txid;
        $this->address = $address;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getTransactionTxid()
    {
        return $this->transaction_txid;
    }

    /**
     * @param string $transaction_txid
     */
    public function setTransactionTxid($transaction_txid)
    {
        $this->transaction_txid = $transaction_txid;
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
}