<?php

namespace App\Model\Bitcoin\ScriptParser\Dto;


class ScriptSignatureDto
{
    /**
     * Two compounds of the signature
     * @var string
     */
    private $r;
    private $s;

    private $signature_type;

    /**
     * ScriptSignatureDto constructor.
     * @param string $r
     * @param $s
     * @param $sig_type - typ podpisu
     */
    public function __construct($r, $s, $sig_type)
    {
        $this->r = $r;
        $this->s = $s;
        $this->signature_type=$sig_type;
    }


    /**
     * @return string
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @return mixed
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @return mixed
     */
    public function getSignatureType()
    {
        return $this->signature_type;
    }
}