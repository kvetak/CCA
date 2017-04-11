<?php

namespace App\Model;

/**
 * Class RelationCreateDto
 * @package App\Model
 *
 * Přepravní třída pro vytváření relaci v Neo4j databázi
 */
class RelationCreateDto
{
    /**
     * @var string
     */
    private $sourceNode;

    /**
     * @var array
     */
    private $sourceAttributes;

    /**
     * @var string
     */
    private $destNode;

    /**
     * @var array
     */
    private $destAttributes;

    /**
     * @var array
     */
    private $relationOptions;


    /**
     * @return string
     */
    public function getSourceNode()
    {
        return $this->sourceNode;
    }

    /**
     * @param string $sourceNode
     */
    public function setSourceNode($sourceNode)
    {
        $this->sourceNode = $sourceNode;
    }

    /**
     * @return array
     */
    public function getSourceAttributes()
    {
        return $this->sourceAttributes;
    }

    /**
     * @param array $sourceAttributes
     */
    public function setSourceAttributes($sourceAttributes)
    {
        $this->sourceAttributes = $sourceAttributes;
    }

    /**
     * @return string
     */
    public function getDestNode()
    {
        return $this->destNode;
    }

    /**
     * @param string $destNode
     */
    public function setDestNode($destNode)
    {
        $this->destNode = $destNode;
    }

    /**
     * @return array
     */
    public function getDestAttributes()
    {
        return $this->destAttributes;
    }

    /**
     * @param array $destAttributes
     */
    public function setDestAttributes($destAttributes)
    {
        $this->destAttributes = $destAttributes;
    }

    /**
     * @return array
     */
    public function getRelationOptions()
    {
        return $this->relationOptions;
    }

    /**
     * @param array $relationOptions
     */
    public function setRelationOptions($relationOptions)
    {
        $this->relationOptions = $relationOptions;
    }
}