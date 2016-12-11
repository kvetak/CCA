<?php

namespace App\Model;
/**
 * Bazova trieda pre vytvaranie modelov pracujucich s MongoDB.
 *
 * Class BaseMongoModel
 * @package App\Model
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class BaseMongoModel
{
    /**
     * Identifikator MongoDb databaze.
     * @var string
     */
    protected static $database   = '';
    /**
     * Identifikator MongoDB kolekcie.
     * @var string
     */
    protected static $collection = '';

    /**
     * App\Model\MongoConnection
     * @var
     */
    protected $mongoConnection;

    public function __construct()
    {
        $this->mongoConnection = MongoConnection::connect(static::$database);
    }

    /**
     * @param null $collection
     * @return \MongoCollection|\MongoDB
     */
    public function collection($collection = null)
    {
        $collectionIdentifier = $collection ?: static::$collection;
        return $this->mongoConnection->{$collectionIdentifier};
    }

    /**
     * Ziskanie objektov v kolekcii.
     * @param int $limit        - limit pre mnozstvo dokumentov, ktore maju byt vybrane z kolekcie
     * @param int $skip         - pocet dokumentov, ktore maju byt pri vybere preskocene
     * @param array $items      - definicia projekcie
     * @return \MongoCursor
     */
    public function findAll($limit = 0, $skip = 0, $items = [])
    {
        return $this->mongoConnection->{static::$collection}
            ->find([], $items)
            ->limit($limit)
            ->skip($skip);
    }
}
