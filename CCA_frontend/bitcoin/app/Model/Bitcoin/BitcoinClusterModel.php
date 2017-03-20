<?php
namespace App\Model\Bitcoin;
use App\Model\Bitcoin\Dto\BitcoinAddressDto;
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
        $dto->setAddresses($this->bitcoinAddressModel->getAddressesInCluster($id));
        return $dto;
    }

    /**
     * Vrátí cluster ve kterém je daná adresa
     *
     * @param BitcoinAddressDto $addressDto
     * @return BitcoinClusterDto Cluster
     */
    public function getClusterByAddress(BitcoinAddressDto $addressDto)
    {
        return $this->getCluster($addressDto->getClusterId());
    }

    /**
     * Získání všech tagů, spojených s adresami v clusteru
     */
    public function getTags()
    {

    }

    /**
     * Je daná adresa v nějakém clusteru?
     *
     * @param BitcoinAddressDto $addressDto
     * @return bool
     */
    public function isInCluster(BitcoinAddressDto $addressDto)
    {
        return $addressDto->getClusterId() != null;
    }

    /**
     * Získání celkového zůstatku všech adres v clusteru
     * @return int Celkový zůstatek v clusteru
     */
    public function getBalance(BitcoinClusterDto $dto)
    {
        $addresses=$this->bitcoinAddressModel->getAddressesInCluster($dto->getId());
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
     * @param bool $remove_addresses Pokud je nastaven na false, tak u adres v databázi zůstane uložen odkaz na cluster
     * Pokud je nastaven na true, pak se u adres nastaví ukazatel na cluster na null
     */
    private function deleteCluster($cluster_id, $remove_addresses=false)
    {
        if ($remove_addresses)
        {
            $cluster=$this->getCluster($cluster_id);
            foreach ($cluster->getAddresses() as $address)
            {
                $address->setClusterId(null);
                $this->bitcoinAddressModel->updateNode($address);
            }
        }
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
            $address->setClusterId($new_id);
            $this->bitcoinAddressModel->updateNode($address);
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
            $address->setClusterId($clusterDto->getId());
            $this->bitcoinAddressModel->storeNode($address);
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
            $cluster_id=$addressDto->getClusterId();
            if ($cluster_id != null)
            {
                if (!isset($clusters[$cluster_id]))
                {
                    $clusters[$cluster_id] = $this->getCluster($cluster_id);
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
                $this->addAddressesToCluster($clusters[0],$not_in_cluster);
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
