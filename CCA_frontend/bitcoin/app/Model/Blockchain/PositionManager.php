<?php
/**
 * @file: PositionManager.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Blockchain;

use App\Model\Blockchain\Parser\PositionDto;
use App\Model\NeoConnection;

class PositionManager
{
    const BTC_POSITION_NODE_NAME="BTC_POSITION";

    /**
     * Klient databáze
     * @var \GraphAware\Neo4j\Client\ClientInterface
     */
    private $neoConnection;

    public function __construct()
    {
        $this->neoConnection=NeoConnection::connect();
    }

    /**
     * Store parsing position into database
     * @param PositionDto $positionDto
     */
    public function store(PositionDto $positionDto)
    {
        // delete old position
        $this->deletePosition();

        $this->neoConnection->run("CREATE (n:".self::BTC_POSITION_NODE_NAME." {
            filename: '".$positionDto->getFilename()."', 
            position: '".$positionDto->getPosition()."'
        })");
    }

    public function deletePosition()
    {
        $this->neoConnection->run("MATCH (n:".self::BTC_POSITION_NODE_NAME.") DELETE n");
    }

    /**
     * Load position from database
     * @return PositionDto
     */
    public function load()
    {
        $query_result=$this->neoConnection->run("MATCH (n:".self::BTC_POSITION_NODE_NAME.") return n");
        if ($query_result->size() == 0)
        {
            return null;
        }
        $values=$query_result->firstRecord()->valueByIndex(0)->values();
        return new PositionDto($values["filename"],$values["position"]);
    }
}