<?php
namespace App\Model\Bitcoin;
use App\Model\Bitcoin\Dto\BitcoinAddressDto;
use App\Model\Bitcoin\Dto\BitcoinAddressTagDto;
use App\Model\Bitcoin\Dto\BitcoinAddressUsageDto;
use App\Model\Bitcoin\Dto\BitcoinClusterDto;

/**
 * Model pre pracu so zhlukami adries.
 *
 * Class ClusterModel
 * @package App\Model
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 *
 */
class BitcoinClusterModel extends BaseBitcoinModel
{
    /**
     * Název node, jak je uložen v databázi
     */
    const NODE_NAME="cluster";

    const DB_ID="id";


    const DB_REL_CONTAINS="contains";

    /**
     * @var BitcoinAddressModel
     */
    private $bitcoinAddressModel;


    private static $instance;

    /**
     * Tovární metoda, vrací instanci třídy
     *
     * @return BitcoinClusterModel volané třídy
     */
    public static function getInstance()
    {
        if (self::$instance == null){
            self::$instance= new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        parent::__construct();
        $this->bitcoinAddressModel=BitcoinAddressModel::getInstance();
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }

    /**
     * @param array $data_array
     * @return BitcoinClusterDto
     */
    private function array_to_dto(array $data_array)
    {
        $dto = new BitcoinClusterDto();
        $dto->setId($data_array[self::DB_ID]);
        return $dto;
    }

    /**
     * @param BitcoinClusterDto $dto
     * @return array
     */
    private function dto_to_array(BitcoinClusterDto $dto)
    {
        $array=array();
        $array[self::DB_ID] = $dto->getId();
        return $array;
    }

    /**
     * Získá z databáze cluster
     * @param $id int - Identifikátor clusteru
     * @return BitcoinClusterDto
     */
    public function getCluster($id)
    {
        $data=$this->findOne(self::DB_ID,$id);
        $dto=$this->array_to_dto($data);
        return $dto;
    }

    /**
     * Smaže všechny uzly v databázi
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }

    /**
     * Vrátí cluster ve kterém je daná adresa
     *
     * @param BitcoinAddressDto $addressDto
     * @return BitcoinClusterDto Cluster
     */
    public function getClusterByAddress(BitcoinAddressDto $addressDto)
    {
        $data=$this->bitcoinAddressModel->findCluster($addressDto);
        if (count($data) == 0)
        {
            return null;
        }
        $clusterDto = $this->array_to_dto($data[0]);
        return $clusterDto;
    }

    /**
     * Najde všechny adresy v daném clusteru
     * @param BitcoinClusterDto $clusterDto
     * @return array<BitcoinAddressDto> Adresy v clusteru
     */
    public function getAddressesInCluster(BitcoinClusterDto $clusterDto)
    {
        $addresses = $this->findRelatedNodes(
            array(self::DB_ID => $clusterDto->getId()),
            self::DB_REL_CONTAINS
        );
        $addressDtos=array();
        foreach ($addresses as $address)
        {
            $addressDtos[]=$this->bitcoinAddressModel->array_to_dto($address);
        }
        return $addressDtos;
    }

    /**
     * Najde adresy v clusteru a k nim použití adres
     *
     * @param BitcoinClusterDto $clusterDto
     * @return array<getDisplayAddressInCluster>
     */
    public function getDisplayAddressInCluster(BitcoinClusterDto $clusterDto)
    {
        $data = $this->findRelatedNodesAndTheirRelationCount(
            array(self::DB_ID => $clusterDto->getId()),
            self::DB_REL_CONTAINS,
            BitcoinAddressModel::DB_REL_PARTICIPATE
        );

        $addressUsedDtos=array();
        $count = count($data[self::RETURN_NODES]);
        for ($i=0 ; $i < $count ; $i++)
        {
            $addressDto=BitcoinAddressModel::array_to_dto($data[self::RETURN_NODES][$i]);

            $dto=new BitcoinAddressUsageDto();
            $dto->setAddress($addressDto);
            $dto->setUsed($data[self::RETURN_RELATION_COUNT][$i] > 0);
            $addressUsedDtos[]=$dto;
        }

        return $addressUsedDtos;
    }

    /**
     * Získání všech tagů, spojených s adresami v clusteru
     * @param BitcoinClusterDto $dto
     * @return array<BitcoinTagDto>
     */
    public function getTags(BitcoinClusterDto $dto)
    {
        $tags=$this->findMultipleHopRelation(
            array(self::DB_ID => $dto->getId()),
            array(self::DB_REL_CONTAINS, BitcoinAddressModel::DB_REL_HAS_TAG)
        );

        $tagDtos=array();
        foreach ($tags as $tag)
        {
            $tagDtos[]=$this->array_to_dto($tag);
        }
        return $tagDtos;
    }

    /**
     * Najde všechny adresy v clusteru a k nim související tagy
     * @param BitcoinClusterDto $dto
     * @return array<BitcoinAddressTagDto> Dto obsahující adresy a jejich související tagy
     */
    public function getAddressTags(BitcoinClusterDto $dto)
    {
        $addresses=$this->getAddressesInCluster($dto);

        $result=array();
        foreach ($addresses as $address)
        {
            $tags=$this->bitcoinAddressModel->getTags($address);
            $result[]=new BitcoinAddressTagDto($address,$tags);
        }
        return $result;
    }

    /**
     * Je daná adresa v nějakém clusteru?
     *
     * @param BitcoinAddressDto $addressDto
     * @return bool
     */
    public function isInCluster(BitcoinAddressDto $addressDto)
    {
        $cluster=$this->bitcoinAddressModel->findCluster($addressDto);
        return count($cluster) > 0;
    }

    /**
     * Získání celkového zůstatku všech adres v clusteru
     * @param BitcoinClusterDto $dto
     * @return int Celkový zůstatek v clusteru
     */
    public function getBalance(BitcoinClusterDto $dto)
    {
        $addresses=$this->getAddressesInCluster($dto);
        $balance=0;
        foreach ($addresses as $address)
        {
            $balance+=$address->getBalance();
        }
        return $balance;
    }

    /**
     * Vloží nový cluster do databáze
     * @param BitcoinClusterDto $dto
     */
    public function insertNode(BitcoinClusterDto $dto)
    {
        $array=$this->dto_to_array($dto);
        $this->insert($array);
    }

    /**
     * Vrátí použitelné ID pro nový cluster
     */
    public function getNewClusterId()
    {
        return $this->maximum(self::DB_ID, self::DATATYPE_INTEGER)+1;
    }

    /**
     * Vymaže z databáze záznam o daném clusteru
     *
     * @param int $cluster_id ID clusteru, který bude smazán
     */
    private function deleteCluster($cluster_id)
    {
        $this->delete(self::DB_ID,$cluster_id);
    }

    /**
     * Spojení více clusterů do jednoho
     * Smaže všechny zadané clustery a adresy z nich vloží do nového clusteru
     *
     * // TODO optimalizace, nemazat všechny staré clustery, ale najít největší a do něj přiřadit ostatní adresy
     *
     * @param $clusters array<BitcoinClusterDto> Clustery které se mají spojit
     * @return BitcoinClusterDto Výsledný cluster
     */
    private function mergeClusters(array $clusters)
    {
        $addresses=array();
        foreach ($clusters as $cluster)
        {
            $addresses=array_merge($addresses,$cluster->getAddresses());
            $this->deleteCluster($cluster->getId());
        }
        return $this->createCluster($addresses);
    }

    /**
     * Vytvoří cluster se zadanými adresami
     * @param array $addresses
     * @return BitcoinClusterDto Nově vytvořený cluster
     */
    private function createCluster(array $addresses)
    {
        $new_id=$this->getNewClusterId();
        $dto= new BitcoinClusterDto();
        $dto->setId($new_id);

        $this->insertNode($dto);

        foreach ($addresses as $address)
        {
            $this->makeRelation(
                array(self::DB_ID => $new_id),
                array(self::RELATION_TYPE => self::DB_REL_CONTAINS),
                BitcoinAddressModel::NODE_NAME,
                array(BitcoinAddressModel::DB_ADDRESS => $address->getAddress())
            );
        }
        return $dto;
    }

    /**
     * Přidá adresy do clusteru
     * Předpokládá že aktuálně adresy v žádném clustery nejsou
     *
     * @param BitcoinClusterDto $clusterDto
     * @param array $addresses
     */
    private function addAddressesToCluster(BitcoinClusterDto $clusterDto, array $addresses)
    {
        foreach ($addresses as $address)
        {
            $this->makeRelation(
                array(self::DB_ID => $clusterDto->getId()),
                array(self::RELATION_TYPE => self::DB_REL_CONTAINS),
                BitcoinAddressModel::NODE_NAME,
                array(BitcoinAddressModel::DB_ADDRESS => $address->getAddress())
            );
        }
    }

    /**
     * Spojení adres do jednoho clusteru
     * Pokud jsou adresy aktuálně v nějakých clusterech, tak jsou tyto clustery spojeny
     * Adresy které v clusteru nebyly jsou do něj přiřazeny
     *
     * @param array<string> $addresses - Seznam adres které mají být v jednom clusteru
     */
    public function clusterizeAddresses(array $addresses)
    {
        // spojovat do clusteru jednu nebo žádnou adresu nemá smysl
        if (count($addresses) < 2)
        {
            return;
        }

        $clusters=array();
        $not_in_cluster=array();

        /**
         * zjištění příslušnosti adres do clusterů
         **/
        foreach ($addresses as $address)
        {
            $addressDto = $this->bitcoinAddressModel->findByAddress($address);
            $cluster=$this->getClusterByAddress($addressDto);
            if ($cluster != null)
            {
                $cluster->setAddresses($this->getAddressesInCluster($cluster));
                if (!isset($clusters[$cluster->getId()]))
                {
                    $clusters[$cluster->getId()] = $cluster;
                }
            }
            else
            {
                $not_in_cluster[]=$addressDto;
            }
        }

        /**
         * Spojení adres do jednoho clusteru
         */
        $cluster_count=count($clusters);
        // žádný cluster zatím není, je třeba všechny adresy vložit do nového clusteru
        if ($cluster_count == 0)
        {
            // vlož všechny adresy do novéh clusteru
            $this->createCluster($not_in_cluster);
        }
        // celkem jsou adresy v jednom clusteru, ale některé mohou být i mimo něj, takže je třeba je do clusteru vložit
        elseif ($cluster_count == 1)
        {
            if (count($not_in_cluster) > 0)
            {
                $this->addAddressesToCluster(array_values($clusters)[0],$not_in_cluster);
            }
        }
        // adresy jsou ve více clusterech a navíc mohou být i mimo něj
        else
        {
            $new_cluster=$this->mergeClusters($clusters);
            if (count($not_in_cluster) > 0)
            {
                $this->addAddressesToCluster($new_cluster,$not_in_cluster);
            }
        }
    }
}
