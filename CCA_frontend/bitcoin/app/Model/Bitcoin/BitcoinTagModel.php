<?php

namespace App\Model\Bitcoin;


use App\Model\Bitcoin\Dto\BitcoinTagDto;
use App\Model\Exceptions\TagNotFoundException;

class BitcoinTagModel extends BaseBitcoinModel
{
    const NODE_NAME="tag";

    const DB_ID="id",
        DB_TAG="tag",
        DB_URL="url";

    private static $instance;

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }

    /**
     * Tovární metoda, vrací instanci třídy
     *
     * @return BitcoinTagModel volané třídy
     */
    public static function getInstance()
    {
        if (self::$instance == null){
            self::$instance= new self();
        }
        return self::$instance;
    }

    /**
     * BitcoinTagModel constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }


    /**
     * @param array $array
     * @return BitcoinTagDto
     */
    public static function array_to_dto(array $array)
    {
        $dto = new BitcoinTagDto();

        $dto->setId($array[self::DB_ID]);
        $dto->setTag($array[self::DB_TAG]);
        $dto->setUrl($array[self::DB_URL]);

        return $dto;
    }

    /**
     * @param BitcoinTagDto $dto
     * @return array
     */
    private function dto_to_array(BitcoinTagDto $dto)
    {
        $array=array();

        $array[self::DB_ID]=$dto->getId();
        $array[self::DB_TAG]=$dto->getTag();
        $array[self::DB_URL]=$dto->getUrl();

        return $array;
    }

    /**
     * Vrátí použitelné ID pro nový cluster
     */
    public function getNewTagId()
    {
        return $this->maximum(self::DB_ID, self::DATATYPE_INTEGER)+1;
    }

    /**
     * Smaže všechny tagy z databáze
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }

    /**
     * Vloží nový tag do databáze
     * @param BitcoinTagDto $dto
     */
    private function storeNode(BitcoinTagDto $dto)
    {
        $array=$this->dto_to_array($dto);
        $this->insert($array);
    }

    /**
     * Vloží nový tag do databáze
     * U tagu není nutné vyplňovat ID, model jej určí a nastaví automaticky
     *
     * @param BitcoinTagDto $dto - Tag, který má být vložen, bez ID
     * @return BitcoinTagDto $dto - Tag s nastaveným ID
     */
    public function insertTag(BitcoinTagDto $dto)
    {
        $dto->setId($this->getNewTagId());
        $this->storeNode($dto);
        return $dto;
    }

    /**
     * Změní hodnoty tagu v databázi
     * @param BitcoinTagDto $dto
     */
    public function updateNode(BitcoinTagDto $dto)
    {
        $array=$this->dto_to_array($dto);
        $this->update(self::DB_ID,$dto->getId(),$array);
    }

    /**
     * Vyhledá a vrátí tag podle ID
     *
     * @param int $tag_id identifikátor tagu
     * @return BitcoinTagDto
     * @throws TagNotFoundException
     */
    public function findById($tag_id)
    {
        $data=$this->findOne(self::DB_ID,$tag_id);
        if (count($data) == 0)
        {
            throw new TagNotFoundException("Not found tag with id: ".$tag_id);
        }
        return $this->array_to_dto($data);
    }

    /**
     * Najde všechny existující tagy s daným textem tagu
     *
     * @param string $tag Text tagu který se má vyhledat
     * @return array|null Pokud nalezne záznam pak jej vrátí, pokud nenalezne záznam vrací null
     */
    public function existByTag($tag)
    {
        $data=$this->find(self::DB_TAG,$tag);
        if (count($data) == 0)
        {
            return null;
        }
        $returnArray=array();
        foreach ($data as $tagData)
        {
            $returnArray[]=$this->array_to_dto($tagData);
        }

        return $returnArray;
    }
}