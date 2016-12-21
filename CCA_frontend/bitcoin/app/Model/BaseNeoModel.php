<?php
/**
 * @file: BaseNeoModel.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model;
use GraphAware\Common\Result\Record;

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
    private $neoConnection;

    public function __construct()
    {
        $this->neoConnection=NeoConnection::connect();
    }

    /**
     * Metoda pro získání jména uzlu, které jde použít v databázi
     * Pro měny se skládá z názvu měny a poté z typu uzlu
     * @return string - název uzlu
     */
    protected abstract function getEffectionNodeName();

    /**
     * Vrátí kolektci všech uzlů daného typu
     * @return array
     */
    protected function collection()
    {
        $result = $this->neoConnection->run("MATCH (list:".$this->getEffectionNodeName().") RETURN list");
        return $result->getRecords();
    }

    /**
     * Vrací počet uzlů daného jména v databázi
     * @return int
     */
    protected function count()
    {
        $result = $this->neoConnection->run("MATCH (n:".$this->getEffectionNodeName().") RETURN count(n)");

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
        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectionNodeName().") 
            return n 
            order by id(n) desc 
            skip ".$skip." 
            limit ".$limit."");

        $result=array();
        $records=$query_result->records();
        foreach ($records as $record)
        {
            $result[$record->valueByIndex(0)->identity()]=$this->node_to_array($record);
        }

        return $result;
    }

    /**
     * Vrátí uzel s danou hodnotou
     * @param $property String - property, podle které se bude vyhledávat
     * @param $value String - hodnota property která se bude hledat
     * @param mixed $limit - Limit na počet uzlů, které se mají vyhledat, false|0 = bez limitu
     * @param mixed $skip - Počet uzlů které se mají ve výpisu přeskočit, false|0 = nepřeskakovat
     * @return array -
     */
    protected function find($property, $value, $limit = false, $skip = false)
    {
        $limit_str = ($limit !== false && $limit > 0) ? "limit ".$limit : "";
        $skip_str = ($skip !== false && $skip > 0) ? "skip ".$skip : "";


        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectionNodeName().") 
            WHERE n.".$property." = \"".$value."\" 
            return n 
            ".$limit_str." 
            ".$skip_str);

        if ($query_result->size() == 0)
        {
            return array();
        }
        return $this->node_to_array($query_result->firstRecord());
    }

    /**
     * Najde první uzel v databázi podle seřazeno daného atributu vzestupně nebo sestupně
     * @param String $order_by - atribut podle kterého se bude řadit, pokud je roven NULL, bude se řadit podle ID uzlu
     * @param bool $descending -  true = bude se řadit sestupně, false = vzestupně
     * @return array - pole atributů uzlu
     */
    protected function findFirst($order_by = null, $descending = false)
    {
        $ordering_property=($order_by == null) ? "id(n)" : $order_by;
        $desc = ($descending) ? "desc" : "";

        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectionNodeName().") 
            return n 
            order by ".$ordering_property."  ".$desc." 
            limit 1");

        return $this->node_to_array($query_result->firstRecord());
    }


    private function node_to_array(Record $record)
    {
        return $record->valueByIndex(0)->values();
    }

}