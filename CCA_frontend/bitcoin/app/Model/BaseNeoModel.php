<?php
/**
 * @file: BaseNeoModel.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model;
use GraphAware\Common\Result\Record;
use GraphAware\Neo4j\Client\Formatter\Type\Node;

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

    const RELATION_TYPE="type",
        RELATION_PROPERTIES="properties";

    const RETURN_NODES="nodes",
        RETURN_COMMON_NODE="common_node",
        RETURN_RELATIONS="relations",
        RETURN_RELATION_COUNT="rel_cnt";

    /**
     * Klient databáze
     * @var \GraphAware\Neo4j\Client\ClientInterface
     */
    private $neoConnection;

    protected function __construct()
    {
        $this->neoConnection=NeoConnection::connect();
    }

    /**
     * Metoda pro získání jména uzlu, které jde použít v databázi
     * Pro měny se skládá z názvu měny a poté z typu uzlu
     * @return string - název uzlu
     */
    protected abstract function getEffectiveNodeName($node_name=null);

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
     * Pro všechny uzly daného typu v databázi nastaví dané hodnoty
     * Určeno pro vyčištění databáze při resetu, kdy se mají smazat jen některé hodnoty uzlů
     *
     * @param array $attributes
     */
    protected function setOnAllNodes(array $attributes)
    {
        $set_array=array();
        foreach ($attributes as $key => $attribute)
        {
            $set_array[]="n.".$key." = '".$attribute."'";
        }

        $query="MATCH (n:".$this->getEffectiveNodeName().")
            SET ".implode(" , ",$set_array)
            . "RETURN n;";

        $this->neoConnection->run($query);
    }

    /**
     * Vrací uzly z databáze, seřazené od nejnovějšího
     * @param int $limit - Maximální počet uzlů, který bude vrácen
     * @param int $skip - počet uzlů který bude přeskočen od začátku výstupu
     * @return array <ID ; array <property_name ; property_value>>;
     */
    protected function findAllNodes($limit = null, $skip = null, $order_by=null, $order_datatype=null)
    {
        $query= "MATCH (n:".$this->getEffectiveNodeName().") 
            return n "
            .$this->order_by_clause($order_by,$order_datatype,true);
            if ($skip != null) {
                $query .= " skip " . $skip . " 
            ";
            }
            if ($limit != null) {
                $query.=" limit ".$limit;
            }

        $query_result=$this->neoConnection->run($query);

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
     * Vrátí všechny uzly, které mají některou z hodnot, obsaženou v poli
     *
     * @param $property_name string - hodnota, podle tkeré se filtruje
     * @param $array array - hledané hodnoty
     */
    protected function findByArray($property_name, array $array, $order_by = null, $descending = false)
    {
        $where_arr=array();
        foreach ($array as $val)
        {
            $where_arr[]="n.".$property_name." = \"".$val."\" ";
        }

        $query="MATCH (n:".$this->getEffectiveNodeName().")
            WHERE ".implode(" or ", $where_arr)."
            return n
            ".$this->order_by_clause($order_by,null,$descending);

        $query_result=$this->neoConnection->run($query);
        return $this->nodes_to_array($query_result->records());
    }

    /**
     * Najde první uzel v databázi podle seřazeno daného atributu vzestupně nebo sestupně
     * @param String $order_by - atribut podle kterého se bude řadit, pokud je roven NULL, bude se řadit podle ID uzlu
     * @param string $datatype - datový typ položky, podle které se má řadit
     * @param bool $descending -  true = bude se řadit sestupně, false = vzestupně
     * @return array - pole atributů uzlu
     */
    protected function findFirst($order_by = null, $datatype = null, $descending = false)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().") 
            return n "
            .$this->order_by_clause($order_by,$datatype,$descending)."
            limit 1";

        $query_result=$this->neoConnection->run($query);

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
     * Vloží do databáze pole záznamů, přičemž každý ze záznamů vloží unikátně
     *
     * @param array $nodes pole uzlů, přičemž každý uzel je pole typu [atribut] => hodnota
     * @param string $merging_attribute Atribut na zakádě kterého spojovat uzly
     * (pokud mají 2 uzly tento atribut stejný, nevytvoří se nový a ostatní hodnoty zůstanou nezměněny)
     */
    protected function bulkInsertUnique(array $nodes, $merging_attribute)
    {
        $nodes_attr=array();
        $attr_keys=array();
        foreach ($nodes as $node_values)
        {
            $node_attr=array();
            foreach ($node_values as $key => $value)
            {
                $node_attr[]= "$key: '$value'";
                $attr_keys[]=$key;
            }
            $nodes_attr[]= "{ ".implode(",",$node_attr). " }";
        }

        $attr_keys=array_unique($attr_keys);
        $attr_strings=array();
        foreach ($attr_keys as $key)
        {
            if ($key != $merging_attribute) {
                $attr_strings[] = "n.$key = props.$key";
            }
        }

        $query="FOREACH (props IN [".implode(", ",$nodes_attr)."]| 
            MERGE (n:".$this->getEffectiveNodeName()."{
                $merging_attribute: props.$merging_attribute
            })";

        if (count ($attr_strings) > 0) {
            $query.=" ON CREATE SET ".implode(",", $attr_strings)." ";
        }
        $query.=")";

        $this->neoConnection->run($query);
    }

    /**
     * Vytvoří více relací v jednom kroku
     * @param array $relations
     */
    protected function bulkCreateRelations(array $relations)
    {
        $i=0;
        $match_query="";
        $with_attrs=array();
        $create_query="";
        foreach ($relations as $relation)
        {
            $match_query.="MATCH (n$i:".$this->getEffectiveNodeName($relation->getSourceNode()).") ".$this->serializeWhereAttributes("n$i",$relation->getSourceAttributes())." 
            ";
            $match_query.="MATCH (n".($i+1).":".$this->getEffectiveNodeName($relation->getDestNode()).") ".$this->serializeWhereAttributes("n".($i+1),$relation->getDestAttributes())."
            ";
            $with_attrs[]="n$i";
            $with_attrs[]="n".($i+1);
            $create_query.="CREATE UNIQUE (n$i)-".$this->serializaRelationOption($relation->getRelationOptions())."->(n".($i+1).")
            ";
            $i+=2;
        }
        $query=$match_query." WITH ".implode(",",$with_attrs)."
        ".$create_query;

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
        $this->neoConnection->run("MATCH (n:".$this->getEffectiveNodeName().") detach delete n");
    }

    /**
     * Smaže uzly odpovídající dané podmínce
     * @param $propety
     * @param $value
     */
    protected function delete($propety, $value)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().") WHERE n.".$propety." = \"".$value."\" detach delete n";
        $this->neoConnection->run($query);
    }

    protected function maximum($property,$datatype=null)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().") return max(".$this->propertyDatatype($property,$datatype).")";
        $result=$this->neoConnection->run($query);

        return $result->firstRecord()->values()[0];
    }

    /**
     * Vytvoří vazbu mezi dvěma uzly
     *
     * @param array $src_attributes hodnoty atributů, podle kterých se vyhledá zdrojový uzel
     * @param array $relationship_options vlastnosti vazby
     * @param string $dest_node_type Typ cílového uzlu
     * @param array $dest_attributes hodnoty atributů, podle kterých se pozná cílový uzel
     */
    protected function makeRelation(array $src_attributes, array $relationship_options, $dest_node_type, array $dest_attributes)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().") ".$this->serializeWhereAttributes("n",$src_attributes)."
             MATCH (n2:".$this->getEffectiveNodeName($dest_node_type).") ".$this->serializeWhereAttributes("n2",$dest_attributes)."
             Create (n) -".$this->serializaRelationOption($relationship_options)."-> (n2)";

        $this->neoConnection->run($query);
    }

    /**
     * Zruší vazby daného jména na určeném uzlu
     *
     * @param array $src_attributes
     * @param string $relation_name Jméno vazby, která se má zrušit
     */
    protected function deleteRelations(array $src_attributes, $relation_name)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().")-[rel:".$relation_name."]->(r)
            ".$this->serializeWhereAttributes("n",$src_attributes)."
            DELETE rel";

        $this->neoConnection->run($query);
    }

    /**
     * @param array $src_attributes
     * @param $relation_name
     * @param string $source_node
     * @return array
     */
    protected function findRelatedNodes(array $src_attributes, $relation_name, $source_node=null)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName($source_node).")-[rel:".$relation_name."]->(r)
            ".$this->serializeWhereAttributes("n",$src_attributes)."
            return r";

        $result=$this->neoConnection->run($query);
        return $this->nodes_to_array($result->records());
    }

    /**
     * Najde uzly ze kterých vede vazba do zadaného uzlu
     *
     * @param array $src_attributes
     * @param $relation_name
     * @return array
     */
    protected function findRelatedNodesBackward(array $src_attributes, $relation_name)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().")<-[rel:".$relation_name."]-(r)
            ".$this->serializeWhereAttributes("n",$src_attributes)."
            return r";

        $result=$this->neoConnection->run($query);
        return $this->nodes_to_array($result->records());
    }

    /**
     * Najde uzly vzdálený přes několik uzlý skrz relace
     *
     * @param array $src_attributes
     * @param array $relation_names
     * @param null $source_node
     * @return array
     */
    protected function findMultipleHopRelation(array $src_attributes, array $relation_names, $source_node = null)
    {
        $relation_count=0;
        $relation_query="";
        foreach ($relation_names as $relation)
        {
            $relation_query.="-[:$relation]->(n".$relation_count++.")
            ";
        }

        $query="MATCH (n:".$this->getEffectiveNodeName($source_node).")
            ".$relation_query."
            ".$this->serializeWhereAttributes("n",$src_attributes)."
            return n".($relation_count-1);

        $result=$this->neoConnection->run($query);
        return $this->nodes_to_array($result->records());
    }

    /**
     * Vrátí sousední uzly propojené danou relací a atributy této relace
     *
     *
     * @param array $src_attributes
     * @param string $relation_name
     * @return array Pole obsahuje na indexu self::RETURN_NODES pole nalezených uzlů a jejich properties,
     *      na indexu  self::RETURN_RELATIONS pole relací a jejich properties
     */
    protected function findRelatedNodesAndRelation(array $src_attributes, $relation_name)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().")"
            ."-[rel:".$relation_name."]->(m) "
            ."  ".$this->serializeWhereAttributes("n",$src_attributes)."
            return m,rel";

        $result=$this->neoConnection->run($query);
        $return=array();

        foreach ($result->records() as $key => $record)
        {
            $return[self::RETURN_NODES][$key] = $record->values()[0]->values();
            $return[self::RETURN_RELATIONS][$key] = $record->values()[1]->values();
        }
        return $return;
    }

    /**
     * Vyhledá několik uzlů a spojí jejich dané vazby na jeden určený uzel.
     * Všechny ostatní uzly jsou smazány
     *
     * Je určeno pro spojování clusterů
     *
     * @param $relation_name - název spojované relace
     * @param $where_property - atribut podle kterého se identifikují uzly
     * @param $target_node - hodnota $where_property, která určuje, který uzel zůstane
     * @param $merging_nodes - pole hodnot $where_property, které určují uzly, které budou spojeny a smazány
     */
    protected function mergeRelationNodes($relation_name, $where_property, $target_node, array $merging_nodes)
    {
        $query="MATCH (n:".$this->getEffectiveNodeName().")-[rel:".$relation_name."]->(a) WHERE n.".$where_property." IN [";
        $query.=implode(",",$merging_nodes)."]
        ";

        $query.="MATCH (c:".$this->getEffectiveNodeName().") WHERE c.".$where_property." = ".$target_node."
        DELETE rel
        DELETE n
        CREATE (c)-[:".$relation_name."]->(a)";

        $this->neoConnection->run($query);
    }

    /**
     * Najde uzel a všechny jeho sousedy podle dané relace, vyhledává podle jednoho ze sousedů
     *
     * @param string $node_name Název uzlu souseda
     * @param string $relation_name název relace
     * @param array $src_attributes atributy určující počátečního souseda
     * @return array pole kde [self::RETURN_NODES] obsahuje data sousedů,
     * [self::RETURN_COMMON_NODE] obsahuje jeden společný uzel
     */
    protected function findNodesWithSameNeighbor($node_name, $relation_name, array $src_attributes)
    {
        $query="MATCH (a:".$this->getEffectiveNodeName($node_name).")<-[:".$relation_name."]-(n)-[:".$relation_name."]->(b)
         ".$this->serializeWhereAttributes("a",$src_attributes)." RETURN a,b,n";

        $result=$this->neoConnection->run($query);

        $return=array();
        $base_node=false;
        foreach ($result->records() as $key => $record)
        {
            if (!$base_node){
                $return[self::RETURN_NODES][] = $record->values()[0]->values();
                $base_node=true;
            }
            $return[self::RETURN_NODES][] = $record->values()[1]->values();
            $return[self::RETURN_COMMON_NODE] = $record->values()[2]->values();
        }

        return $return;
    }

    /**
     * Vrací uzly a relace mezi nimi, jde $forward_step po směru zadané relace a současně $backward_steps kroků proti směru
     *
     * @param array $src_attributes
     * @param $relation_name
     * @param int $forward_step
     * @param int $backward_steps
     * @return array [self::RETURN_NODES] - nalezené uzly
     *  [self::RETURN_RELATIONS] - relace mezi uzly
     */
    protected function findRelatedNodesAndRelations(array $src_attributes, $relation_name, $forward_step=2, $backward_steps=0)
    {
        $return_vars=array("n");
        $query="MATCH (n:".$this->getEffectiveNodeName().") ".$this->serializeWhereAttributes("n",$src_attributes)."
        ";

        if ($backward_steps > 0)
        {
            $query.="OPTIONAL MATCH (b)-[back_rel:".$relation_name."*1..".$backward_steps."]->(n)
            ";
            $return_vars[]="b";
            $return_vars[]="back_rel";
        }
        if ($forward_step > 0)
        {
            $query.="OPTIONAL MATCH (n)-[rel:".$relation_name."*1..".$forward_step."]->(f)
            ";
            $return_vars[]="rel";
            $return_vars[]="f";
        }

        $query.= " return ".implode(",",$return_vars);
        $result=$this->neoConnection->run($query);

        $return=array(
            self::RETURN_NODES => array(),
            self::RETURN_RELATIONS => array()
        );

        $node_ids_in_result=array();
        $rel_ids_in_result=array();

        foreach ($result->records() as $record)
        {
            foreach ($record->values() as $value)
            {
                if ($value instanceof Node)
                {
                    if (!in_array($value->identity(),$node_ids_in_result))
                    {
                        $node_ids_in_result[]=$value->identity();
                        $return[self::RETURN_NODES][] = $value->values();
                    }
                }
                elseif (is_array($value))
                {
                    foreach ($value as $array_field) {
                        if (!in_array($array_field->identity(),$rel_ids_in_result)){
                            $rel_ids_in_result[]=$array_field->identity();
                            $return[self::RETURN_RELATIONS][] = $array_field->values();
                        }
                    }
                }
            }
        }

        return $return;
    }



    /**
     * Najde uzel n, dle $src_attributes, podle vazby $relation_name, najde sousední uzly r,
     * vrací uzly r a množství jejich vazeb, které se jmenují $count_relation_name
     *
     * @param array $src_attributes Atributy zdrojového uzlu
     * @param string $relation_name Název relace mezi uzly n a r
     * @param string $count_relation_name název relace která se počítá u uzlů r
     * @return array Pole obsahuje na indexu self::RETURN_NODES uzly r
     *  a na indexu self::RETURN_RELATION_COUNT kardinalitu vazeb
     */
    protected function findRelatedNodesAndTheirRelationCount(array $src_attributes, $relation_name, $count_relation_name)
    {
        $query = "MATCH (n:".$this->getEffectiveNodeName().") -[:".$relation_name."]->(r)
            ".$this->serializeWhereAttributes("n",$src_attributes)."
            WITH r
            OPTIONAL MATCH (r)-[rel:".$count_relation_name."]->()
            RETURN r, count(rel) as cnt";

        $result = $this->neoConnection->run($query);
        $return=array();

        foreach ($result->records() as $key => $record)
        {
            $return[self::RETURN_NODES][$key] = $record->values()[0]->values();
            $return[self::RETURN_RELATION_COUNT][$key] = $record->values()[1];
        }
        return $return;
    }

    /**
     * Vytvoří index pro daný typ uzlů a na daný atribut
     * @param $property
     */
    protected function createIndex($property)
    {
        $query="CREATE INDEX ON :".$this->getEffectiveNodeName()."($property)";
        $this->neoConnection->run($query);
    }

    /**
     * @param array $relationship_options
     *  struktura pole (
     *      RELATION_TYPE => "typ",
     *      RELATION_PROPERTIES => array(
     *          "prop1" => "value"
     *      )
     * )
     *
     * @return string Serializovaná klauzule
     */
    private function serializaRelationOption(array $relationship_options)
    {
        if (count($relationship_options) == 0)
        {
            return "";
        }

        $query="[";
        if (isset($relationship_options[self::RELATION_TYPE]))
        {
            $query.=":".$relationship_options[self::RELATION_TYPE];
        }

        if (isset($relationship_options[self::RELATION_PROPERTIES]))
        {
            $properties=array();
            foreach ($relationship_options[self::RELATION_PROPERTIES] as $prop => $value)
            {
                $properties[]="$prop:'$value'";
            }
            $query.=" {".implode(",",$properties)."}";
        }
        return $query."]";
    }

    /**
     * Vytvoří obsah where klauzule podle zadaných atributů
     *
     * @param $node_name string Jméno uzlu v klauzuli
     * @param array $attributes atributy klíč => hodnota kterých má uzel nabývat
     * @return string
     */
    private function serializeWhereAttributes($node_name,array $attributes)
    {
        if (count($attributes) == 0)
        {
            return "";
        }

        $where_array=array();
        foreach ($attributes as $key => $attribute)
        {
            $where_array[]="$node_name.$key = '".$attribute."'";
        }
        return "WHERE ".implode(" AND ",$where_array);
    }

    /**
     * Přidá k property daného uzlu funkci pro konverzi datových typů
     *
     * @param $property string dotazovaná vlastnost
     * @param $datatype string|null Datový typ, null pokud datový typ není specifikován
     * @return string část dotazovací klauzule
     */
    private function propertyDatatype($property,$datatype)
    {
        // pokud je specifikován datový typ property, aplikuj datový typ
        if ($datatype != null){
            return $datatype."(n.".$property.")";
        }
        // pokud není datový typ použij jen property
        else
        {
            return "n.".$property;
        }
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
            $order = $this->propertyDatatype($property,$datatype);
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

    /**
     * Převede záznamy získané z databáze do reprezentace v poli
     * @param array $records
     * @return array
     */
    private function nodes_to_array(array $records)
    {
        $result=array();
        foreach ($records as $record)
        {
            $result[]=$record->valueByIndex(0)->values();;
        }
        return $result;
    }

}