<?php

namespace App\Model\Bitcoin;

use App\Model\Bitcoin\Dto\BitcoinAddressDto;
use App\Model\Bitcoin\Dto\BitcoinTagDto;
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
        DB_REL_PROP_BALANCE="balance",
        DB_REL_HAS_TAG="has_tag";

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

    public static function array_to_dto($array)
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
    public function getTags(BitcoinAddressDto $addressDto)
    {
        $tagNodes = $this->findRelatedNodes(
            array(self::DB_ADDRESS => $addressDto->getAddress()),
            self::DB_REL_HAS_TAG
        );

        $tags=array();
        foreach ($tagNodes as $tagNode)
        {
            $tags[]=BitcoinTagModel::array_to_dto($tagNode);
        }
        return $tags;
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
     * Zkontorluje jestli se u dané adresy nachází daný tag
     * @param BitcoinAddressDto $addressDto Adresa u které se hledá tag
     * @param BitcoinTagDto $tagDto Hledaný tag
     * @return bool true v případě že u dané adresy již je přiřazen daný tag, jinak false
     */
    public function hasTag(BitcoinAddressDto $addressDto, BitcoinTagDto $tagDto)
    {
        $tags=$this->getTags($addressDto);

        foreach ($tags as $tag)
        {
            if ($tag->getId() == $tagDto->getId())
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Přidá k adrese tag
     * Pokud již existuje vazba s tímto tagem, nestane se nic
     *
     * @param BitcoinAddressDto $addressDto - Adresa, ke které se má přidat tag
     * @param BitcoinTagDto $tagDto - Tag, který se má přidat k adrese - je nutné aby obsahoval ID!!
     */
    public function addTag(BitcoinAddressDto $addressDto, BitcoinTagDto $tagDto)
    {
        if (!$this->hasTag($addressDto, $tagDto))
        {
            $this->makeRelation(
                array(self::DB_ADDRESS => $addressDto->getAddress()),
                array(self::RELATION_TYPE => self::DB_REL_HAS_TAG),
                BitcoinTagModel::NODE_NAME,
                array(BitcoinTagModel::DB_ID => $tagDto->getId())
            );
        }
    }
}
