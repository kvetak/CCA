<?php

namespace App\Model\Bitcoin;
use App\Model\BaseNeoModel;
use App\Model\Bitcoin\Dto\BitcoinBlockDto;
use App\Model\Exceptions\BlockNotFoundException;

/**
 * Model pre pracu s blokmi.
 *
 * Class BitcoinBlockModel
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 * @author Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */
class BitcoinBlockModel extends BaseBitcoinModel
{
    /**
     * Název node, jak je uložen v databázi
     */
    const NODE_NAME="block";

    /**
     * Názvy jednotlivých atributů modelu, tak jak jsou uloženy v databázi
     */
    const DB_HASH="hash",
        DB_HEIGHT="height",
        DB_NEXT_BLOCK_HASH="next_block_hash",
        DB_PREV_BLOCK_HASH="prev_block_hash",
        DB_SIZE="size_t",
        DB_SUM_OF_FEES="sum_of_fees",
        DB_SUM_OF_INPUTS="sum_of_inputs",
        DB_SUM_OF_OUTPUT="sum_of_outputs",
        DB_TIME="time",
        DB_TRANSACTION_COUNT="transactions";

    /**
     * BitcoinBlockModel constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }

    /**
     * Převede uzel z reprezentace v poli na reprezentaci v Dto objektu
     * v poli je uzel uložen např v databázi
     *
     * @param $array string Pole hodnot uzlu
     * @return BitcoinBlockDto
     */
    private function array_to_dto($array)
    {
        $dto=new BitcoinBlockDto();

        $dto->setHash($array[self::DB_HASH]);
        $dto->setHeight($array[self::DB_HEIGHT]);
        $dto->setNextBlockHash($array[self::DB_NEXT_BLOCK_HASH]);
        $dto->setPreviousBlockHash($array[self::DB_PREV_BLOCK_HASH]);
        $dto->setSize($array[self::DB_SIZE]);
        $dto->setSumOfFees($array[self::DB_SUM_OF_FEES]);
        $dto->setSumOfInputs($array[self::DB_SUM_OF_INPUTS]);
        $dto->setSumOfOutputs($array[self::DB_SUM_OF_OUTPUT]);
        $dto->setTime($array[self::DB_TIME]);
        $dto->setTransactionsCount($array[self::DB_TRANSACTION_COUNT]);
        return $dto;
    }

    /**
     * Převede uzel z reprezentace v Dto do pole
     *
     * @param BitcoinBlockDto $dto Dto s hodnotami uzlu
     * @return array Pole s hodnotami uzlu
     */
    private function dto_to_array(BitcoinBlockDto $dto)
    {
        $array=array();

        $array[self::DB_HASH]=$dto->getHash();
        $array[self::DB_HEIGHT]=$dto->getHeight();
        $array[self::DB_NEXT_BLOCK_HASH]=$dto->getNextBlockHash();
        $array[self::DB_PREV_BLOCK_HASH]=$dto->getPreviousBlockHash();
        $array[self::DB_SIZE]=$dto->getSize();
        $array[self::DB_SUM_OF_FEES]=$dto->getSumOfFees();
        $array[self::DB_SUM_OF_INPUTS]=$dto->getSumOfInputs();
        $array[self::DB_SUM_OF_OUTPUT]=$dto->getSumOfOutputs();
        $array[self::DB_TIME]=$dto->getTime();
        $array[self::DB_TRANSACTION_COUNT]=$dto->getTransactionsCount();

        return $array;
    }

    /**
     * Uloží uzel do databáze
     *
     * @param BitcoinBlockDto $dto
     */
    public function storeNode(BitcoinBlockDto $dto)
    {
        $values=$this->dto_to_array($dto);
        $this->insert($values);
    }

    /**
     * Smaže všechny bloky z databáze
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }

    /**
     * Aktualizace hodnot v bloku
     * @param BitcoinBlockDto $dto
     */
    public function updateBlock(BitcoinBlockDto $dto)
    {
        $this->update(self::DB_HASH,$dto->getHash(),
            $this->dto_to_array($dto));
    }

    /**
     * Vrací uzly z databáze, seřazené od nejnovějšího
     * @param int $limit - Maximální počet uzlů, který bude vrácen
     * @param int $skip - počet uzlů který bude přeskočen od začátku výstupu
     * @param array $items      - definicia projekcie
     * @return array <ID ; array <property_name ; property_value>>;
     */
    public function findAll($limit = 0, $skip = 0, $items = [])
    {
       $data=$this->findAllNodes($limit,$skip,$items, self::DB_HEIGHT, BaseNeoModel::DATATYPE_INTEGER);
       $blocks=array();

       foreach ($data as $block_data)
       {
            $blocks[]=$this->array_to_dto($block_data);
       }

       return $blocks;
    }

    /**
     * Ziskanie informacii o poslednom importovanom bloku.
     * @return BitcoinBlockDto
     */
    public function getLastBlock()
    {
        return $this->array_to_dto($this->findFirst(self::DB_HEIGHT,self::DATATYPE_INTEGER,true));
    }

    /**
     * Vyhladanie bloku podla hashu.
     * @param $hash            - hash bloku
     * @return BitcoinBlockDto
     * @throws BlockNotFoundException pokud blok není nalezen, vyhodí výjimku
     */
    public function findByHash($hash)
    {
        $block=$this->existByHash($hash);
        if ($block == null){
            throw new BlockNotFoundException("Not found block with hash ".$hash);
        }
        return $block;
    }

    /**
     * Ověří existenci bloku podle hashe
     * Pokud existuje pak jej vrátí
     *
     * @param $hash - hash bloku
     * @return BitcoinBlockDto|null, null = block nebyl nalezen
     */
    public function existByHash($hash)
    {
        $data=$this->findOne(self::DB_HASH,$hash);
        if (count($data) == 0)
        {
         return null;
        }
        return $this->array_to_dto($data);
    }

    /**
     * Vyhladanie bloku podla vysky bloku.
     * @param $height         - vyska bloku
     * @return BitcoinBlockDto
     */
    public function findByHeight($height)
    {
        $block=$this->existByHeight($height);
        if ($block == null){
            throw new BlockNotFoundException("Not found block with height ".$height);
        }
        return $block;
    }

    /**
     * Ověří existenci bloku podle jeho výšky
     * Pokud blok existuje, pak jej vrátí
     *
     * @param $height
     * @return BitcoinBlockDto|null
     */
    public function existByHeight($height)
    {
        $data=$this->findOne(self::DB_HEIGHT,$height);
        if (count($data) == 0)
        {
            return null;
        }
        return $this->array_to_dto($data);
    }

    /**
     * Kontrola ci su transakcie v bloku overene.
     * @param $blockHeight
     * @param $currentBlockHeight
     * @return bool
     */
    public static function isConfirmed($blockHeight, $currentBlockHeight)
    {
        return ($currentBlockHeight - $blockHeight) >= 6;
    }

    /**
     * Vyhladavanie zaznamu v kolekci.
     * @param $params
     * @return bool
     */
    public static function exists($params)
    {
        $c      = get_called_class();
        $model  = new $c;
        return ! empty($model->collection()->findOne($params,['_id'=>true]));
    }

    /**
     * Vrací počet bitcoin bloků v databázi
     * @return int
     */
    public function getCount()
    {
        return $this->count();
    }
}
