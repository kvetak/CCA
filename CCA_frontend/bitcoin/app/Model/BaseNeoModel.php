<?php
/**
 * @file: BaseNeoModel.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model;

/**
 * Class BaseNeoModel
 * @package App\Model
 *
 * Základní třída pro modely pracující s Neo4j databází
 */
abstract class BaseNeoModel
{
    /**
     * Klient databáze
     * @var \GraphAware\Neo4j\Client\ClientInterface
     */
    protected $neoConnection;

    public function __construct()
    {
        $this->neoConnection=NeoConnection::connect();
    }

    /**
     * @return string - jméno uzlů použitých v daném modelu
     */
    protected abstract function getNodeName();


    /**
     * Vrátí kolektci všech uzlů daného typu
     * @return array
     */
    protected function collection()
    {
        $result = $this->neoConnection->run("MATCH (list:".$this->getNodeName().") RETURN list");
        return $result->getRecords();
    }

    /**
     * Vrací počet uzlů daného jména v databázi
     * @return int
     */
    protected function count()
    {
        $result = $this->neoConnection->run("MATCH (n:".$this->getNodeName().") RETURN count(n)");

        return $result->firstRecord()->valueByIndex(0);
    }

    /**
     * Vrací uzly z databáze, seřazené od nejnovějšího
     * @param int $limit - Maximální počet uzlů, který bude vrácen
     * @param int $skip - počet uzlů který bude přeskočen od začátku výstupu
     * @param array $items      - definicia projekcie
     * @return array <ID ; array <property_name ; property_value>>;
     */
    public function findAll($limit = 0, $skip = 0, $items = [])
    {
        $query_result=$this->neoConnection->run("MATCH (n:".$this->getNodeName().") return n order by id(n) desc skip ".$skip." limit ".$limit."");

        $result=array();
        $records=$query_result->records();
        foreach ($records as $record)
        {
            $result[$record->valueByIndex(0)->identity()]=$record->valueByIndex(0)->values();
        }

        return $result;
    }

}