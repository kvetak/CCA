<?php

namespace App\Model\Bitcoin\Dto;


class BitcoinTransactionPaymentDto
{
    /**
     * Address used between transactions
     * @var string
     */
    private $address;

    /**
     * Txid of source transaction
     * @var string
     */
    private $pays_from;

    /**
     * Txid of destination transaction
     * @var string
     */
    private $pays_to;

    /**
     * Ammount of TBC spent in transaction
     * @var double
     */
    private $value;

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getPaysFrom()
    {
        return $this->pays_from;
    }

    /**
     * @param mixed $pays_from
     */
    public function setPaysFrom($pays_from)
    {
        $this->pays_from = $pays_from;
    }

    /**
     * @return mixed
     */
    public function getPaysTo()
    {
        return $this->pays_to;
    }

    /**
     * @param mixed $pays_to
     */
    public function setPaysTo($pays_to)
    {
        $this->pays_to = $pays_to;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}