<?php

namespace App\Model\Bitcoin\Dto;


class BitcoinTagDto
{
    /**
     * Identifikátor tagu
     * @var int
     */
    private $id;

    /**
     * Název tagu
     * @var string
     */
    private $tag;

    /**
     * URL adresa ze které je tento tag znám
     * @var string
     */
    private $url;

    /**
     * Zdroj stažení identifikátoru
     * @var int
     */
    private $source;

    /**
     * Typ zdroje
     * @var int
     */
    private $source_type;

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
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * @param int $source_type
     */
    public function setSourceType($source_type)
    {
        $this->source_type = $source_type;
    }
}