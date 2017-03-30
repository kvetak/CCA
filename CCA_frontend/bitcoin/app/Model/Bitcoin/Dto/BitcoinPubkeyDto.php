<?php

namespace App\Model\Bitcoin\Dto;


class BitcoinPubkeyDto
{
    /**
     * Komprimovaný veřejný klíč
     * @var string
     */
    private $compressedPubkey;

    /**
     * RIPEMD160 hash nekomprimovaného veřejného klíče
     * @var string
     */
    private $ripe;

    /**
     * RIPEMD160 hash komprimovaného veřejného klíče
     * @var string
     */
    private $compressedRipe;

    /**
     * @return string
     */
    public function getCompressedPubkey()
    {
        return $this->compressedPubkey;
    }

    /**
     * @param string $compressedPubkey
     */
    public function setCompressedPubkey($compressedPubkey)
    {
        $this->compressedPubkey = $compressedPubkey;
    }

    /**
     * @return string
     */
    public function getRipe()
    {
        return $this->ripe;
    }

    /**
     * @param string $ripe
     */
    public function setRipe($ripe)
    {
        $this->ripe = $ripe;
    }

    /**
     * @return string
     */
    public function getCompressedRipe()
    {
        return $this->compressedRipe;
    }

    /**
     * @param string $compressedRipe
     */
    public function setCompressedRipe($compressedRipe)
    {
        $this->compressedRipe = $compressedRipe;
    }
}