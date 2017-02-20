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
    const DATATYPE_INTEGER="toInteger",
        DATATYPE_STRING="toString";

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
    protected abstract function getEffectiveNodeName();

    /**
     * Vrátí kolektci všech uzlů daného typu
     * @return array
     */
    protected function collection()
    {
        $result = $this->neoConnection->run("MATCH (list:".$this->getEffectiveNodeName().") RETURN list");
        return $result->getRecords();
    }

    /**
     * Vrací počet uzlů daného jména v databázi
     * @return int
     */
    protected function count()
    {
        $result = $this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") RETURN count(n)");

        return $result->firstRecord()->valueByIndex(0);
    }

    /**
     * Vrací uzly z databáze, seřazené od nejnovějšího
     * @param int $limit - Maximální počet uzlů, který bude vrácen
     * @param int $skip - počet uzlů který bude přeskočen od začátku výstupu
     * @param array $items      - definicia projekcie
     * @return array <ID ; array <property_name ; property_value>>;
     */
    protected function findAllNodes($limit = 0, $skip = 0, $items = [], $order_by=null, $order_datatype=null)
    {
        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") 
            return n "
            .$this->order_by_clause($order_by,$order_datatype,true).
            " skip ".$skip." 
            limit ".$limit);

        $result=array();
        $records=$query_result->records();
        foreach ($records as $record)
        {
            $result[$record->valueByIndex(0)->identity()]=$this->node_to_array($record);
        }

        return $result;
    }

    /**
     * Vrátí uzly s danou hodnotou
     * @param $property String - property, podle které se bude vyhledávat
     * @param $value String - hodnota property která se bude hledat
     * @param mixed $limit - Limit na počet uzlů, které se mají vyhledat, false|0 = bez limitu
     * @param mixed $skip - Počet uzlů které se mají ve výpisu přeskočit, false|0 = nepřeskakovat
     * @return array uzel s danou hodnotu, pokud není uzel nalezen vrací prázdné pole
     */
    protected function find($property, $value, $limit = false, $skip = false)
    {
        $limit_str = ($limit !== false && $limit > 0) ? "limit ".$limit : "";
        $skip_str = ($skip !== false && $skip > 0) ? "skip ".$skip : "";

        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") 
            WHERE n.".$property." = \"".$value."\" 
            return n 
            ".$limit_str." 
            ".$skip_str);

        if ($query_result->size() == 0)
        {
            return array();
        }
        $result=array();
        $records=$query_result->records();
        foreach ($records as $record)
        {
            $result[$record->valueByIndex(0)->identity()]=$this->node_to_array($record);
        }
        return $result;
    }


    /**
     * Vrátí jeden uzel s danou hodnotou
     * @param $property String - property, podle které se bude vyhledávat
     * @param $value String - hodnota property která se bude hledat
     * @param mixed $limit - Limit na počet uzlů, které se mají vyhledat, false|0 = bez limitu
     * @param mixed $skip - Počet uzlů které se mají ve výpisu přeskočit, false|0 = nepřeskakovat
     * @return array uzel s danou hodnotu, pokud není uzel nalezen vrací prázdné pole
     */
    protected function findOne($property, $value, $skip = false)
    {
        $skip_str = ($skip !== false && $skip > 0) ? "skip ".$skip : "";

        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") 
            WHERE n.".$property." = \"".$value."\"
            return n 
            limit 1
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
        $query_result=$this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") 
            return n"
            .$this->order_by_clause($order_by,null,$descending)."
            limit 1");

        return $this->node_to_array($query_result->firstRecord());
    }

    /**
     * Vloží uzel do databáze
     *
     * @param array $values - hodnoty uzlu
     */
    protected function insert(array $values)
    {
        $attributes_array=array();
        foreach ($values as $key => $value)
        {
            $attributes_array[] = "$key: '$value'";
        }
        $attributes = implode(", ",$attributes_array);

        $query="CREATE (n:".$this->getEffectiveNodeName()."{".
            $attributes.
        "})";

        $this->neoConnection->run($query);
    }

    /**
     * Upravý hodnoty uzlů v databázi
     *
     * @param $where_attr string Název atributu, podle kterého se nalezne updatovaný uzel
     * @param $where_value string Hodnota atributu, podle které se bude uzel hledat
     * @param $attributes array atributy, které se mají nastavit
     */
    protected function update($where_attr, $where_value, array $attributes)
    {
        $set_array=array();
        foreach ($attributes as $key => $attribute)
        {
            $set_array[]="n.".$key." = '".$attribute."'";
        }

        $query="MATCH (n:".$this->getEffectiveNodeName().")
            WHERE n.".$where_attr." = '".$where_value."'
            SET ".implode(" , ",$set_array)
            . "RETURN n;";

        $this->neoConnection->run($query);
    }

    /**
     * Smaže všechny uzly daného typu
     */
    protected function deleteAll()
    {
        $this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") delete n");
    }

    /**
     * Vytvoří klauzuli pro řazení výstupu, podle daných parametrů
     * @param $property string Hodnota, podle které se řadí
     * @param $datatype string Datový typ property
     * @param $desc bool Sestupnost řazení
     * @return string klauzule "order by"
     */
    private function order_by_clause($property,$datatype, $desc)
    {
        // pokud není nic specifikováno, aplikuj výchozí řazení podle id
        if ($property == null) {
            $order = "id(n)";
        }
        // řazení podle property
        else
        {
            // pokud je specifikován datový typ property, aplikuj datový typ
            if ($datatype != null){
                $order= $datatype."(n.".$property.")";
            }
            // pokud není datový typ použij jen property
            else
            {
                $order = "n.".$property;
            }
        }

        if ($desc)
        {
            $order.=" desc";
        }
        return "order by ".$order;
    }

    private function node_to_array(Record $record)
    {
        return $record->valueByIndex(0)->values();
    }

}