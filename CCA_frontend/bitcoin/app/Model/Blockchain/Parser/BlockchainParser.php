<?php

/**
 * Main file for blockchain parser library
 *
 * Author: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 14:40
 */

namespace App\Model\Blockchain\Parser;
/*
require_once __DIR__ . "/BlockDto.php";
require_once __DIR__ . "/BlockParser.php";
require_once __DIR__ . "/Exceptions.php";
require_once __DIR__ . "/FileReader.php";
require_once __DIR__ . "/InputTransactionDto.php";
require_once __DIR__ . "/OutputTransactionDto.php";
require_once __DIR__ . "/PositionDto.php";
require_once __DIR__ . "/TransactionDto.php";
require_once __DIR__ . "/TypeReader.php";*/

/**
 * Class Blockchain
 * @package BlockChainParser
 *
 * Main class for blockchain parser library
 * contains public interface of the library
 *
 */
class BlockchainParser
{
    /**
     * Path to blockchain directory, where block files are stored
     */
    private $blockchainDir;

    /**
     * Instance of file reader class, which reads blockchain files
     */
    private $fileReader;

    /**
     * Store result of parsing operation
     */
    private $parsedBlocks=null;

    /**
     * @var BlockParser - instance of object for parsing blocks
     */
    private $blockParser;

    /**
     * @brief Construct Blockchain
     *
     * @param String $blockchainDir - path to blockchain directory, where block files are stored, including trailing slash
     */
    public function __construct($blockchainDir)
    {
        $this->blockchainDir = $blockchainDir;
        $this->fileReader=new FileReader($blockchainDir);
        $this->blockParser=new BlockParser($this->fileReader);
    }

    /**
     * @brief Parse blocks from blockchain, start from current position
     *
     * @param Integer $count Number of blockchain blocks to parse.
     * Parsing will stop after $count blocks are parsed.
     * Result is stored and can be later accessed via getResult() method.
     * @return array<BlockDto> Parsed blocks.
     */
    public function parse($count)
    {
        $this->parsedBlocks=array();
        for ($i=0 ; $i < $count ; $i++)
        {
            $this->parsedBlocks[]=$this->blockParser->parseBlock();
        }
        return $this->parsedBlocks;
    }

    /**
     * @brief Get result of last parse operation
     * @return mixed (array<BlockDto>|null) Array of parsed blocks from last parsing. Null if none parsing happened before.
     */
    public function getResult()
    {
        return $this->parsedBlocks;
    }

    /**
     * @brief Set position in blockchain, where to begin parsing from
     * @param PositionDto $positionDto
     *
     * Parser will open given file and set given offset
     */
    public function startFrom(PositionDto $positionDto)
    {
        $this->fileReader->setPosition($positionDto);
    }

    /**
     * @brief Return current position in blockchain
     * @return PositionDto - current position in blockchain
     *
     * This position can be later imported via startFrom method.
     */
    public function getPosition()
    {
        return $this->fileReader->getPosition();
    }
}