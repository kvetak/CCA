<?php

namespace App\Model\Bitcoin;
use Underscore\Types\Arrays;

/**
 * Model pre pracu s blokmi.
 *
 * Class BitcoinBlockModel
 * @package App\Model
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class BitcoinBlockModel extends BaseBitcoinModel implements \ArrayAccess
{
    /**
     * Název node, jak je uložen v databázi
     */
    const NODE_NAME="block";
    /**
     * Hash bloku
     * @var string
     */
    protected $hash;
    /**
     * Vyska bloku.
     * @var int
     */
    protected $height;
    /**
     * Pocet potvrdeni bloku.
     * @var int
     */
    protected $confirmations;
    /**
     * Obsah modelu bloku.
     * @var array
     */
    protected $blockModel;

    /**
     * BitcoinBlockModel constructor.
     * @param null $blockHash    - hash bloku
     * @param null $blockHeight  - vyska bloku()
     */
    public function __construct($blockHash = null, $blockHeight = null)
    {
        parent::__construct();
        $this->hash    = $blockHash;
        $this->height   = $blockHeight;
        $this->init();
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }


    /**
     * Inicializacta modelu.
     */
    protected function init()
    {
        $this->setModel();
    }

    /**
     * Nacitanie modelu v pripade ze bola zadana vyska bloku alebo hash bloku.
     */
    protected function setModel()
    {
        if (!empty($this->getHeight()))
        {
            $this->blockModel = $this->find("height",$this->getHeight(),1);
        }
        elseif (!empty($this->getHash()))
        {
            $this->blockModel = $this->find("hash",$this->getHash(),1);
        }
    }
    /**
     * Ziskanie hashu bloku.
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Ziskanie vysky bloku.
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Zostavenie podmienok pre vyhladavanie v kolekcii blokov.
     * @return array
     */
    protected function getFindConditions()
    {
        $conditions = [];
        if(!empty($this->getHash())){
            $conditions[] = [
                'hash'  => $this->getHash(),
            ];
        }
        if( ! empty($this->getHeight())){
            $conditions[] = [
                'height'  => (int) $this->getHeight(),
            ];
        }
        return $conditions;
    }

    /**
     * Ziskanie informacii o poslednom importovanom bloku.
     */
    public function getLastBlock()
    {
        return $this->findFirst(null,false);
    }

    /**
     * Kontrola existencie bloku.
     * @return bool
     */
    public function existsBlock()
    {
        return ! empty($this->blockModel);
    }

    /**
     * Vyhladanie bloku podla hashu.
     * @param $hash            - hash bloku
     * @param array $fields    - polozky, ktore maju byt vratene
     * @return array|null
     */
    public static function findByHash($hash, $fields = [])
    {
        $c      = get_called_class();
        $model  = new $c;
        return $model->collection()->findOne(['hash' => $hash], $fields);
    }

    /**
     * Vyhladanie bloku podla vysky bloku.
     * @param $height         - vyska bloku
     * @param array $fields   - polozky, ktore maju byt vratene
     * @return array|null
     */
    public static function findByHeight($height, $fields = [])
    {
        $c      = get_called_class();
        $model  = new $c;
        return $model->collection()->findOne(['height'=> $height], $fields);
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
     * Implementacia Arrays Interface.
     */
    public function offsetSet($offset, $value) {
    }

    public function offsetExists($offset) {
        return isset($this->blockModel[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->blockModel[$offset]);
    }

    public function offsetGet($offset) {
        return Arrays::get($this->blockModel, $offset, null);
    }

    public function getCount()
    {
        return $this->count();
    }
}
