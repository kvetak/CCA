<?php
namespace App\Model\Bitcoin;
use App\Model\CurrencyType;
use Underscore\Types\Arrays;
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

    /**
     * Identifikator zhluku adries.
     * @var \MongoId
     */
    protected $cluster;
    /**
     * Obsah dokumentu z kolekcie zhlukov..
     * @var
     */
    protected $clusterModel;
    /**
     * Zoznam tagov aries patriacich do zhluku.
     * @var array
     */
    protected $tags         = [];
    /**
     * Velkost zhluku adries.
     * @var int
     */
    protected $clusterSize  = 0;

    public function __construct($cluster = null)
    {
        parent::__construct();
        $this->cluster = $cluster;

        if ($cluster != null)
        {
            $this->setClusterModel();
        }
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }


    protected function setClusterModel()
    {
        $this->clusterModel =  $this->find("_id",$this->getCluster(),1);
    }

    /**
     * Ziskanie identifikatoru zhluku
     * @return \MongoId
     */
    public function getCluster()
    {
        return $this->cluster;
    }

    /**
     * Ziskanie poctu adries, ktore patria do zhluku.
     * @return int
     */
    public function getSize()
    {
        if( ! $this->clusterSize){
            $this->clusterSize = $this->collection($this->getAddressCollectionName())->count([
               'cluster'    => $this->getCluster(),
            ]);

        }
        return $this->clusterSize;
    }

    /**
     * Ziskanie zoznamu otatagovanych adries v zhluku.
     * @return array|\MongoCursor
     */
    public function getTags()
    {
        if( ! count($this->tags)){
            $this->tags = $this->collection($this->getAddressCollectionName())->find(
                [
                    'cluster' => $this->getCluster(),
                    'tags'      => [
                        '$exists' => true,
                    ]
                ]
            );
        }
        return $this->tags;
    }

    /**
     * Ziskanie celkovo stavu na adresach vramci zhluku adries.
     */
    public function getBalance()
    {
        $result = $this->collection($this->getAddressCollectionName())->aggregate([
            [
                '$match'    => [
                    'cluster'    => $this->getCluster(),
                    'balance'   => ['$gt'   =>  0,]
                ],
            ],
            [
                '$project' => [
                    'balance'   => true,
                    'cluster'   => true,
                    '_id'       => false,
                ]
            ],
            [
                '$group'    => [
                    '_id'       => '$cluster',
                    'balance'   => ['$sum'  => '$balance',]
                ]
            ]
        ]);
        //Vratenie vysledku vypoctu
        return (double) Arrays::get($result, 'result.0.balance', 0.0);
    }

    /**
     * Ziskanie adries patriacich do zhluku.
     *
     * @param $limit
     * @param int $skip
     * @return \MongoCursor
     *
     */
    public function getAddresses($limit, $skip = 0)
    {
        return $this->collection($this->getAddressCollectionName())->find(
            [
                'cluster' => $this->getCluster(),
            ]
        )->limit($limit)->skip($skip);
    }

    /**
     * Vytvorenie noveho zaznamu v kolekcii zhlukov
     * @return mixed
     */
    public static function initCluster()
    {
        $c      = get_called_class();
        $model  = new $c;
        $d      = ['_id' => new \MongoId()];
        $model->collection(self::$collection)->insert($d);
        return $d['_id'];
    }

    /**
     * Spojenie uz existujucich zhlukov do jedneho.
     * @param array $from   - Pole identifikatorov zhlukov (Mongo - ObjectId), ktore maju byt splnene
     * @param $to           - identifikator cieloveho zhluku
     */
    protected static function mergeClusters($from = [], $to)
    {
        $queryConditions = [
            'cluster' => [
                '$in' => $from
            ]
        ];
        $clusterModelClassName  = get_called_class();
        $c                      = new $clusterModelClassName;
        $addressCollectionName= CurrencyType::collectionName(CurrencyType::addressModel(static::$type));
        $c->collection($addressCollectionName)->update(
            $queryConditions,
            [
                '$set'  => [
                    'cluster'   => $to,
                ]
            ],
            [
                'multi' => true
            ]
        );
        //todo merge records in clusters collection
        //delete OLD clusters
        $c->collection()->remove($queryConditions);
    }

    /**
     * Vytvorenie zhluku
     * @param array $addresses - Zoznam adries, ktore maju byt priradene do spolocneho zhluku.
     */
    public static function createCluster(array $addresses = [])
    {
        $addressModelName = CurrencyType::addressModel(static::$type);
        /**
         * Pomocna datova struktura, v kotrej bude ulozena prislusnost adries do uz existujucich zhlukov.
         */
        $clusterMap = [
            'clusters'      => [],
            'notInCluster'  => []
        ];
        /**
         * Zistenie informacii o prislusnosti jednotlivych adries do zhlukov.
         */
        foreach($addresses as $address){
            $a              = new $addressModelName($address);
            $clusterModel   = $a->getClusterModel();
            if(empty($clusterModel)){
                $clusterMap['notInCluster'][] = $address;
                continue;
            }
            $clusterId = (string)$clusterModel->getCluster();
            if(isset($clusterMap['clusters'][$clusterId])){
                $clusterMap['clusters'][$clusterId][]   = $address;
            }else{
                $clusterMap['clusters'][$clusterId]     = [$address];
            }
        }

        /**
         * Identifikator pre spolocny zhluk adries.
         */
        $clusterId          = null;
        $foundedClusters    = Arrays::size($clusterMap['clusters']);
        /**
         *  V pripade ze bude nutne zlucovat zhluky
         */
        if( $foundedClusters > 1){
            /**
             * Zoradenie zhlukov podla ich velkosti.
             */
            $clustersOrderedBySize = Arrays::sort(Arrays::keys($clusterMap['clusters']), function($value){
                $c      = get_called_class();
                $cluster = new $c(new \MongoId($value));
                return $cluster->getSize();
            }, 'desc');
            /**
             * Novym reprezentantom shluku bude najvacsi, ktory uz existuje.
             */
            $clusterId = new \MongoId($clustersOrderedBySize[0]);
            /**
             * Ziskanie identifikatorov zhlukov, ktore budu spajane.
             */
            $otherClustersIds = Arrays::each(Arrays::rest($clustersOrderedBySize, 1), function($value){
                return new \MongoId($value);
            });
            /**
             * Zjednotenie shlukov do jedneho.
             */
            self::mergeClusters($otherClustersIds, $clusterId);
        }
        /**
         * V pripade ze adresy patria do maximalne jedneho zhluku.
         */
        elseif($foundedClusters == 1){
            $clusterId = new \MongoId(Arrays::get($clusterMap, 'clusters.0'));
        }
        else{
            /**
             * V inom pripade sa vytvori novy zhluk.
             */
            $clusterId = self::initCluster();
        }
        /**
         * Adresy ktore nepatria do aktualne ziadneho zhluku su pridane do zhluku.
         */
        foreach($clusterMap['notInCluster'] as $addressNotInCluster)
        {
            //Priradenie adresy do zhluku.
            (new $addressModelName($addressNotInCluster))->addToCluster($clusterId);
        }
    }
}
