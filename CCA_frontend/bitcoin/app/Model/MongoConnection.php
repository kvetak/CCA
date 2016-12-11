<?php
namespace App\Model;
/**
 * Trieda reprezentujuca pripojenie do MongoDB.
 *
 * Class MongoConnection
 * @package App\Model
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class MongoConnection{

    private static $instance;
    private static $connection;

    /**
     * Metoda realizujuca pripojenie do databaze MongoDB.
     * @param null $database          - Identifikaotor vybranej MongoDB databaze.
     * @return \MongoClient|\MongoDB
     * @throws \Exception
     */
    public static function connect($database = null)
    {
        if(! isset(self::$instance)){
            self::$instance = new MongoConnection();
        }
        if( ! isset(self::$connection)){
            self::$connection = new \MongoClient(config('MONGO_DSN'));
            // TODO - fix config only load default attributes (not the real ones from config file)
//            self::$connection = new \MongoClient("mongodb://147.229.9.77:27017");
        }
        if( ! empty($database)){
            $connectedDb = self::$connection->selectDB($database);
            if(isset($connectedDb)){
                return $connectedDb;
            }else{
                throw new \Exception("Error while connection to desired DB: {$database}");
            }
        }
        return self::$connection;
    }
}