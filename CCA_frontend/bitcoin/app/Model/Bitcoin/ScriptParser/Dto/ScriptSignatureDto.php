<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 10.3.17
 * Time: 14:52
 */

namespace App\Model\Bitcoin\ScriptParser\Dto;


class ScriptSignatureDto
{
    /**
     * Two compounds of the signature
     * @var string
     */
    private $r;
    private $s;

    /**
     * ScriptSignatureDto constructor.
     * @param string $r
     * @param $s
     */
    public function __construct($r, $s)
    {
        $this->r = $r;
        $this->s = $s;
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


}