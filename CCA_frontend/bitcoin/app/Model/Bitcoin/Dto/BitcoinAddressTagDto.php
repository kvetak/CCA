<?php

namespace App\Model\Bitcoin\Dto;


/**
 * Class BitcoinAddressTagDto
 * @package App\Model\Bitcoin\Dto
 *
 * Přepravní objekt pro spojení BTC adresy a souvisejících tagů
 */
class BitcoinAddressTagDto
{
    /**
     * Jedna BTC adresa
     * @var BitcoinAddressDto
     */
    private $address;

    /**
     * Pole tagů příslušejícíh k adrese
     * @var array<BitcoinTagDto>
     */
    private $tags;

    /**
     * BitcoinAddressTagDto constructor.
     * @param BitcoinAddressDto $address
     * @param array $tags
     */
    public function __construct(BitcoinAddressDto $address, array $tags)
    {
        $this->address = $address;
        $this->tags = $tags;
    }

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
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }
}