<?php

namespace App\Model\Bitcoin\Dto;


class BitcoinAddressUsageDto
{
    /**
     * @var BitcoinAddressDto
     */
    private $address;

    /**
     * @var bool
     */
    private $used;

    /**
     * @return BitcoinAddressDto
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param BitcoinAddressDto $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return bool
     */
    public function isUsed()
    {
        return $this->used;
    }

    /**
     * @param bool $used
     */
    public function setUsed($used)
    {
        $this->used = $used;
    }
}