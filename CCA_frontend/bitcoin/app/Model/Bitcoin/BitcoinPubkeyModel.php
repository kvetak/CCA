<?php

namespace App\Model\Bitcoin;


use App\Model\Bitcoin\Dto\BitcoinAddressDto;
use App\Model\Bitcoin\Dto\BitcoinPubkeyDto;
use App\Model\Bitcoin\ScriptParser\BaseScriptParser;
use App\Model\Exceptions\PubkeyNotFoundException;

class BitcoinPubkeyModel extends BaseBitcoinModel
{
    const NODE_NAME="pubkey";

    const DB_COMPRESSED_PUBKEY="compressed_pubkey",
        DB_RIPE="ripe",
        DB_COMPRESSED_RIPE="compressed_ripe";

    const REL_DEFINES_ADDRESS="defines";

    private static $instance;

    /**
     * @var BitcoinAddressModel
     */
    private $bitcoinAddressModel;

    /**
     * @var BitcoinClusterModel
     */
    private $bitcoinClusterModel;

    /**
     * @var BaseScriptParser;
     */
    private $baseScriptParser;

    /**
     * BitcoinPubkeyModel constructor.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->bitcoinAddressModel = BitcoinAddressModel::getInstance();
        $this->bitcoinClusterModel = BitcoinClusterModel::getInstance();
        $this->baseScriptParser = new BaseScriptParser();
    }

    /**
     * Tovární metoda, vrací instanci třídy
     *
     * @return BitcoinPubkeyModel volané třídy
     */
    public static function getInstance()
    {
        if (self::$instance == null){
            self::$instance= new self();
        }
        return self::$instance;
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }

    public static function array_to_dto(array $data)
    {
        $dto = new BitcoinPubkeyDto();
        $dto->setCompressedPubkey($data[self::DB_COMPRESSED_PUBKEY]);
        $dto->setCompressedRipe($data[self::DB_COMPRESSED_RIPE]);
        $dto->setRipe($data[self::DB_RIPE]);
        return $dto;
    }

    private function dto_to_array(BitcoinPubkeyDto $dto)
    {
        $array=array();
        $array[self::DB_COMPRESSED_PUBKEY]=$dto->getCompressedPubkey();
        $array[self::DB_COMPRESSED_RIPE]=$dto->getCompressedRipe();
        $array[self::DB_RIPE]=$dto->getRipe();
        return $array;
    }

    /**
     * Vytvoří v databázi indexy pro hledání bloků
     */
    public function createIndexes()
    {
        $this->createIndex(self::DB_COMPRESSED_PUBKEY);
    }

    /**
     * Smaže všechny bloky z databáze
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }

    /**
     * Uloží uzel do databáze
     * @param BitcoinPubkeyDto $dto
     */
    public function storeNode(BitcoinPubkeyDto $dto)
    {
        $values=$this->dto_to_array($dto);
        $this->insert($values);
    }

    /**
     * Nalezne všechny známé adresy, spojené s daným veřejným klíčem
     *
     * @param BitcoinPubkeyDto $dto
     * @return array<BitcoinAddressDto>
     */
    public function getAddressesForPubkey(BitcoinPubkeyDto $dto)
    {
        $data = $this->findRelatedNodes(
            array(self::DB_COMPRESSED_PUBKEY => $dto->getCompressedPubkey()),
            self::REL_DEFINES_ADDRESS
        );

        $addressDtos=array();
        foreach ($data as $address)
        {
            $addressDtos[]= BitcoinAddressModel::array_to_dto($address);
        }
        return $addressDtos;
    }

    /**
     * Najde klíč podle jeho komprimované verze
     *
     * @param $publicKey
     * @return BitcoinPubkeyDto|null
     * @throws PubkeyNotFoundException
     */
    public function findByCompressesPublicKey($publicKey)
    {
        $pubkey=$this->existByCompressedPublicKey($publicKey);
        if ($pubkey == null){
            throw new PubkeyNotFoundException("Not found public key ".$publicKey);
        }
        return $pubkey;
    }

    public function existByCompressedPublicKey($publicKey)
    {
        $data=$this->findOne(self::DB_COMPRESSED_PUBKEY,$publicKey);
        if (count($data) == 0)
        {
            return null;
        }
        return $this->array_to_dto($data);
    }

    /**
     * Spočítá odpovídající adresy k jednotlivým klíčům
     * @param array $publicKeys
     */
    public function clusterizeKeys(array $publicKeys)
    {
        foreach ($publicKeys as $key)
        {
            $this->clusterizeKey($key);
        }
    }

    /**
     * Vytvoří ze zadaného klíče všechny očekávané adresy a ty vloží do jednoho clusteru
     * Pokud už tento klíč je v databázi, nic se nestane
     *
     * @param $publicKey string hexadecimální reprezentace veřejného klíče
     */
    public function clusterizeKey($publicKey)
    {
        $compressedPubKey=$this->getCompressedPubkey($publicKey);
        if ($this->existByCompressedPublicKey($compressedPubKey) != null)
        {
            return;
        }

        $nonCompressedPubkey=BitcoinLib::decompress_public_key($compressedPubKey)["public_key"];

        // vlož nový záznam o klíči do databáze
        $dto = new BitcoinPubkeyDto();
        $dto->setCompressedPubkey($compressedPubKey);
        $dto->setCompressedRipe(BitcoinLib::ripemd160_hash($compressedPubKey));
        $dto->setRipe(BitcoinLib::ripemd160_hash($nonCompressedPubkey));
        $this->storeNode($dto);

        $addresses=$this->computeAddresses($compressedPubKey,$nonCompressedPubkey);

        // uložení adres a vazeb s adresami do databáze
        foreach ($addresses as $address)
        {
            if ($this->bitcoinAddressModel->addressExists($address) == null)
            {
                $addressDto=new BitcoinAddressDto();
                $addressDto->setAddress($address);
                $this->bitcoinAddressModel->storeNode($addressDto);
            }
            $this->makeRelation(
                array(self::DB_COMPRESSED_PUBKEY => $compressedPubKey),
                array(self::RELATION_TYPE => self::REL_DEFINES_ADDRESS),
                BitcoinAddressModel::NODE_NAME,
                array(BitcoinAddressModel::DB_ADDRESS => $address)
            );
        }
        // vytvoření clusteru z adres, se stejným klíčem
        $this->bitcoinClusterModel->clusterizeAddresses($addresses);
    }

    /**
     * Vrátí komprimovaný veřejný klíč
     * @param $publicKey string hexa klíč, může ýt komprimovaný a nemusí
     * @return string
     */
    private function getCompressedPubkey($publicKey)
    {
        if (BitcoinLib::is_compressed($publicKey))
        {
            return $publicKey;
        }
        else
        {
            return BitcoinLib::compress_public_key($publicKey);
        }
    }

    /**
     * Vypočte předpokládané adresy daného veřejného klíče
     *
     * @param $compressedPubKey
     * @param $nonCompressedPubkey
     * @return array<string>
     */
    private function computeAddresses($compressedPubKey, $nonCompressedPubkey)
    {
        $addresses=array();

        $addresses[]=BitcoinLib::get_address_from_pubkey($compressedPubKey);
        $addresses[]=BitcoinLib::get_address_from_pubkey($nonCompressedPubkey);

        $addresses[]=$this->getP2shAddress($compressedPubKey);
        $addresses[]=$this->getP2shAddress($nonCompressedPubkey);
        return $addresses;
    }

    /**
     * Vytvoří P2SH (script hash) adresu z veřejného klíče
     * vytvoří skript pro platbu typu multisig 1 adresa z 1
     *
     * @param $publicKey string hexa reprezentace veřejného klíče
     */
    private function getP2shAddress($publicKey)
    {
        $script="";
        $script.=bin2hex($this->baseScriptParser->OP_PUSH_NUMBER_1);
        $script.=dechex(strlen($publicKey)/2);
        $script.=$publicKey;
        $script.=bin2hex($this->baseScriptParser->OP_PUSH_NUMBER_1);
        $script.=bin2hex($this->baseScriptParser->OP_CHECKMULTISIG);

        return BitcoinLib::get_address_from_script(BitcoinLib::ripemd160_hash($script));
    }
}