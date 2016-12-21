<?php
/**
 *
 * User: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 14:52
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class PositionDto
 * @package BlockChainParser
 *
 * Data object for that stored information about current position in blockchain
 *
 * Stores current file and position in file
 * This Dto is used to export or set position in blockchain, where to parse.
 */
class PositionDto
{
    /**
     * @var String - Name of file of blockchain, which is to parse
     */
    private $filename;

    /**
     * @var Integer - Offset from beginning of current file
     */
    private $position;

    /**
     * PositionDto constructor.
     * @param String $filename
     * @param int $position
     */
    public function __construct($filename, $position)
    {
        $this->filename = $filename;
        $this->position = $position;
    }

    /**
     * @return String
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param String $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}