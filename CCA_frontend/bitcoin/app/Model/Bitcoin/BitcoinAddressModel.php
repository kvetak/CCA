<?php

namespace App\Model\Bitcoin;

use App\Model\Bitcoin\Dto\BitcoinAddressDto;
use App\Model\Exceptions\TransactionNotFoundException;

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
     * Název node, jak je uložen v databázi
     */
    const NODE_NAME="address";

    /**
     * Názvy jednotlivých atributů modelu, tak jak jsou uloženy v databázi
     */
    const DB_ADDRESS="address",
        DB_BALANCE="balance";

    const DB_REL_PARTICIPATE="participate",
        DB_REL_PROP_BALANCE="balance";

    /**
     * Typ akym bol tag nahrany do kolekcie.
     */
    const WEB_SOURCE_TYPE = 1, //ziskany z web. stranky
        USER_INPUT_TYPE = 2; //uzivatelom zadany


    private static $instance;

    /**
     * Tovární metoda, vrací instanci třídy
     *
     * @return BitcoinAddressModel volané třídy
     */
    public static function getInstance()
    {
        if (self::$instance == null){
            self::$instance= new self();
        }
        return self::$instance;
    }

    public function array_to_dto($array)
    {
        $dto=new BitcoinAddressDto();

        $dto->setAddress($array[self::DB_ADDRESS]);
        $dto->setBalance($array[self::DB_BALANCE]);
        return $dto;
    }


    private function dto_to_array(BitcoinAddressDto $dto)
    {
        $array=array();

        $array[self::DB_ADDRESS]=$dto->getAddress();
        $array[self::DB_BALANCE]=$dto->getBalance();

        return $array;
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }

    /**
     * BitcoinAddressModel constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }


    /**
     * Vytvoří v databázi indexy pro hledání adres
     */
    public function createIndexes()
    {
        $this->createIndex(self::DB_ADDRESS);
    }


    /**
     * Při vymazávání DB, pro všechny adresy vynuluje zůstatek a smaže seznam transakcí
     *
     * Beze změny ponechá tagy, veřejné klíče, cluster
     */
    public function clearAddresses()
    {
        $this->setOnAllNodes(array(
            self::DB_BALANCE => 0,
        ));
        $this->deleteRelations(array(),self::DB_REL_PARTICIPATE);
    }

    /**
     * Uloží uzel do databáze
     *
     * @param BitcoinAddressDto $dto
     */
    public function storeNode(BitcoinAddressDto $dto)
    {
        $values=$this->dto_to_array($dto);
        $this->insert($values);
    }

    /**
     * Smaže všechny bloky
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }



    /**
     * Nalezení informací o BTC adrese
     *
     * @param $address string - BTC adresa
     * @return BitcoinAddressDto
     * @throws TransactionNotFoundException
     */
    public function findByAddress($address)
    {
        $data=$this->findOne(self::DB_ADDRESS,$address);
        if (count($data) == 0)
        {
            throw new TransactionNotFoundException("Address not found: ".$address);
        }
        return $this->array_to_dto($data);
    }

    /**
     * Ověří existenci záznamu o adrese.
     * Pokud existuje, pak vrátí DTO dané adressy
     *
     * @param $address string - hledaná BTC adresa
     * @return BitcoinAddressDto|null
     *      BitcoinAddressDto - V případě že adresa existuje
     *      null - v případě že adresa neexistuje
     */
    public function addressExists($address)
    {
        $data=$this->findOne(self::DB_ADDRESS,$address);
        if (count($data) == 0)
        {
            return null;
        }
        return $this->array_to_dto($data);
    }


    /**
     * Aktualizace hodnot v bloku
     * @param BitcoinAddressDto $dto
     */
    public function updateNode(BitcoinAddressDto $dto)
    {
        $this->update(self::DB_ADDRESS,$dto->getAddress(),
            $this->dto_to_array($dto));
    }

    public function findCluster(BitcoinAddressDto $addressDto)
    {
        return $this->findRelatedNodesBackward(
            array(self::DB_ADDRESS => $addressDto->getAddress()),
                BitcoinClusterModel::DB_REL_CONTAINS
        );
    }

    /**
     * Vrátí transakce kterých se zúčastnila tato adresa
     *
     * @param BitcoinAddressDto $addressDto
     * @return array
     */
    public function getTransactions(BitcoinAddressDto $addressDto)
    {
        $transactions=$this->findRelatedNodes(
            array(self::DB_ADDRESS => $addressDto->getAddress()),
            self::DB_REL_PARTICIPATE
        );
        $transactionDtos=array();
        foreach ($transactions as $transaction)
        {
            $transactionDtos[]=BitcoinTransactionModel::array_to_dto($transaction);
        }
        return $transactionDtos;
    }

    /**
     * Vrátí tagy, které josu spojeny s danou adresou
     *
     * @param BitcoinAddressDto $dto Adresa jejíž tagy vyhledávám
     * @return array<BitcoinTagDto>
     */
    public function getTags(BitcoinAddressDto $dto)
    {

    }


    /**
     * Ziskanie zoznamu adres, ktore patria do zhluku.
     * @param $cluster  - identifikator zhluku
     * @return array Zoznam adries patriacich do zhluku.
     */
    public function getAddressesInCluster($cluster_id){
        $data=$this->find(self::DB_CLUSTER_ID, $cluster_id);
        $addresses=array();

        foreach ($data as $addr_data)
        {
            $addresses[]=$this->array_to_dto($addr_data);
        }
        return $addresses;
    }

    /**
     * Zaeviduje transakci, ve které figurovala daná adresa
     *
     * @param $address string - adresa
     * @param $txid string - identifikátor transakce
     * @param $balance_change float - změna zústatku na dané adrese
     */
    public function addTransactionRecord($address, $txid, $balance_change)
    {
        $addressDto = $this->addressExists($address);
        if ($addressDto == null)
        {
            $addressDto = new BitcoinAddressDto();
            $addressDto->setAddress($address);
            $addressDto->setBalance($balance_change);
            $this->storeNode($addressDto);
        }
        else
        {
            $addressDto->addBalance($balance_change);
            $this->updateNode($addressDto);
        }
        $this->makeRelation(
            array(self::DB_ADDRESS => $address),
            array(self::RELATION_TYPE => self::DB_REL_PARTICIPATE,
                self::RELATION_PROPERTIES => array(
                        self::DB_REL_PROP_BALANCE => $balance_change
                    )
                ),
            BitcoinTransactionModel::NODE_NAME,
            array(BitcoinTransactionModel::DB_TRANS_TXID => $txid)
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
