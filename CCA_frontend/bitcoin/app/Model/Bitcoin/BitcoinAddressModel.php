<?php

namespace App\Model\Bitcoin;

use App\Model\CurrencyType;
use Illuminate\Support\Arr;
use Underscore\Types\Arrays;

/**
 * Model pre pracu s adresami.
 *
 * Class BitcoinAddressModel
 * @package App\Model\Bitcoin
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class BitcoinAddressModel extends BaseBitcoinModel
{
    /**
     * Typy zdrojov tagov adres.
     */
    const BLOCKCHAININFO_SOURCE = 1;
    /**
     * Typ akym bol tag nahrany do kolekcie.
     */
    const WEB_SOURCE_TYPE = 1; //ziskany z web. stranky
    const USER_INPUT_TYPE = 2; //uzivatelom zadany

    public static $collection = 'addresses';

    /**
     * Adresa
     * @var null
     */
    protected $address;
    /**
     * Obsah v kolekcii pre zadanu adresu.
     * @var
     */
    protected $addressModel;
    /**
     * Priznak ci sa menil stav na ucte od nacitania modelu.
     * @var bool
     */
    protected $balanceChanged;

    /**
     * BitcoinAddressModel constructor.
     * @param null $address  - Bitcoin adresa na zaklade, ktorej sa nacita model
     */
    public function __construct($address = null)
    {
        parent::__construct();
        $this->address      = $address;
        $this->init();
    }

    /**
     * Inicializacia modelu.
     */
    protected function init()
    {
        $this->setAddressModel();
        $this->balanceChanged = false;
    }

    /**
     * Metoda pre opatovne nacitanie modelu.
     */
    public function reload()
    {
        $this->init();
    }

    /**
     * Ziskanie adresy
     * @return string/null
     */
    public function getAddress(){
        return $this->address;
    }

    /**
     * Nacitanie modelu pre adresu.
     */
    protected function setAddressModel()
    {
        $this->addressModel = $this->collection()->findOne($this->getFindByAddressCodnitions());
    }

    /**
     * Ziskanie tagov k adrese.
     * @return mixed
     */
    public function getTags()
    {
        return Arrays::get($this->addressModel, 'tags', []);
    }

    /**
     * Kontrola na prislusnost do nejakeho zhluku.
     * @return bool
     */
    public function isInCluster()
    {
        return isset($this->addressModel['cluster']);
    }

    /**
     * Ziskanie poctu transakcii, v ktorých figuru zadaná adresa.
     * @return int
     */
    public function getTransactionsCount()
    {
        return $this->collection(CurrencyType::collectionName(CurrencyType::transactionModel(static::$type)))->count([
           'inputsOutputs.addresses' => $this->getAddress()
        ]);
    }

    /**
     * Zvysenie stavu na "ucte" o zadanu hodnotu.
     * @param double $value
     */
    public function increaseBalanace($value)
    {
        $isBalanceSetted = Arrays::has($this->addressModel, 'balance');
        $operationKey    = $isBalanceSetted ? '$inc' : '$set';
        $this->collection()->findAndModify($this->getFindByAddressCodnitions(), [
            $operationKey => [
                'balance' => (double) $value
            ]
        ]);
        $this->balanceChanged = true;
    }

    /**
     * Znizenie stavu na "ucte" o zadanu hodnotu.
     * @param $value
     */
    public function decreaseBalance($value)
    {
        $isBalanceSetted = Arrays::has($this->addressModel, 'balance');
        $operationKey    = $isBalanceSetted ? '$inc' : '$set';
        $this->collection()->findAndModify($this->getFindByAddressCodnitions(), [
            $operationKey => [
                'balance' => (double) (-1 * $value)
            ]
        ]);
        $this->balanceChanged = true;
    }

    /**
     * Ziskanie podmienok pre vyhladavanie v kolekcii podla adresy.
     * @param null $address
     * @return array
     */
    protected function getFindByAddressCodnitions($address = null)
    {
        return [
            'address' => empty($address) ? $this->getAddress() : null
        ];
    }

    /**
     * Ziskanie modelu pre pracu so zhlukami
     * @return ClusterModel|null
     */
    public function getClusterModel()
    {
        $clusterModelName = CurrencyType::clusterModel(static::$type);
        return $this->isInCluster() ? new $clusterModelName($this->addressModel['cluster']) : null;
    }

    /**
     * Vypocet stavu na ucte.
     * @return double
     */
    public function getBalance()
    {
        /**
         * V pripade, ze sa stav na ucte nemenil od posledneho nacitania modelu a je stav na ucte nahrany
         * tak sa vrati hodnota z modelu.
         */
        if( ! $this->balanceChanged && Arrays::has($this->addressModel, 'balance')){
            return (double) round(Arrays::get($this->addressModel, 'balance'), 8) + 0;
        }
        /**
         * V pripade, ze sa stav na ucte menil za chodu tak je potreba spocitat aktualny stav na ucte.
         */
        $transactionModelName = CurrencyType::transactionModel(static::$type);
        $m = new $transactionModelName();
        $aggregateQuery = [
            [
                '$match' => [
                    'inputsOutputs' => [
                        '$elemMatch'    => [
                            'addresses' => $this->getAddress(),
                            'type'      => BitcoinTransactionModel::INPUTS_OUTPUTS_TYPE_OUTPUT,
                            'spent'     => False,
                        ]
                    ]
                ]
            ],
            [
                '$project' => [
                    'inputsOutputs' => true,
                ]
            ],
            [
                '$unwind' => '$inputsOutputs',
            ],
            [
                '$match'    => [
                    'inputsOutputs.addresses'   => $this->getAddress(),
                    'inputsOutputs.spent'       => false,
                ],
            ],
            [
                '$group'    => [
                    '_id'       => null,
                    'balance'   => ['$sum'  => '$inputsOutputs.value']
                ]
            ],
        ];
        $result = $m->collection()->aggregate($aggregateQuery);
        return Arr::get($result,'result.0.balance', 0);
    }

    /**
     * Ziskanie zoznamu adres, ktore patria do zhluku.
     * @param $cluster  - identifikator zhluku
     * @return array Zoznam adries patriacich do zhluku.
     */
    public static function getAddressesInClusterAsArray($cluster){
        $c      = get_called_class();
        $a  = new $c;
        $r = $a->collection()->aggregate([
            [
                '$match'    => [
                    'cluster'   => $cluster,
                ]
            ],
            [
                '$project'  => [
                    'address'   => true,
                    '_id'       => false,
                ]
            ]
        ]);
        return Arrays::get($r, 'result', []);
    }

    /**
     * Priradenie adresy do zhluku adries.
     * @param \MongoId $clusterId  - identifkator zhluku
     */
    public function addToCluster($clusterId)
    {
        $this->collection()->findAndModify(
            [
                'address'   => $this->getAddress()
            ],
            [
                '$set' => [
                    'balance'   => (double)$this->getBalance(),
                    'cluster'   => $clusterId,
                    'address'   => $this->getAddress(),
                ]
            ],
            null,
            [
                'upsert' => True,
            ]
        );
    }

    /**
     * Kontrola ci u adresy je zaznamenany tag.
     * @param $tag
     * @param $url
     * @param $source
     * @param int $sourceType
     * @return bool
     */
    public function hasTag($tag, $url, $source, $sourceType = self::WEB_SOURCE_TYPE)
    {
        $query = [
            'address'           => $this->getAddress(),
            'tags.url'          => $url,
            'tags.tag'          => $tag,
            'tags.source'       => $source,
            'tags.sourceType'   => $sourceType,
        ];
        $r = $this->collection()->findOne(
            $query,
            [
                '_id'   => true,
            ]
        );
        return ! empty($r);
    }

    /**
     * Pridanie tagu ku adrese
     * @param $tag              - hodnota tagu
     * @param $url              - URL tagu
     * @param $source           - zdroj
     * @param int $sourceType   - typ zdroju
     */
    public function addTag($tag, $url, $source, $sourceType = self::WEB_SOURCE_TYPE)
    {
        /**
         * V pripade, ze zadany tag u adresy neexistuje tak sa tag prida k adrese.
         */
        if(! $this->hasTag($tag, $url, $source, $sourceType)){
            $this->collection()->findAndModify(
                [
                    'address'   => $this->getAddress(),
                ],
                [
                    '$addToSet' => [
                        'tags'  => [
                            'tag'           => (string) $tag,
                            'url'           => (string) $url,
                            'source'        => (int) $source,
                            'sourceType'    => (int) $sourceType
                        ]
                    ]
                ],
                null,
                [
                    'upsert'    => true,
                ]
            );
        }
    }

    /**
     * Ziskanie zoznamu tagov ku zadanym adresam.
     * @param array $addresses
     * @return mixed
     */
    public static function getTagsByAddresses($addresses = [])
    {
        $c  = get_called_class();
        $m  = new $c;
        $result = $m->collection()->aggregate([
            [
                '$match' => [
                    'address'    => ['$in' => array_values($addresses)],
                    'tags.0'     => ['$exists' => true]

                ],
            ],
            [
                '$project'    => [
                    'address' => true,
                    'tags'    => true,
                    '_id'     => false,
                ],
            ]
        ]);
        return $result['result'];
    }
}
