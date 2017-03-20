<?php

namespace App\Model\Bitcoin\Dto;


class BitcoinClusterDto
{
    /**
     * Identifikátor clusteru
     * @var int
     */
    private $id;

    /**
     * Adresy které patří do clusteru
     * @var array<BitcoinAddressDto>
     */
    private $addresses;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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