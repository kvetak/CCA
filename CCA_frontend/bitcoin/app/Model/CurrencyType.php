<?php
namespace App\Model;

use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\Bitcoin\BitcoinBlockModel;
use App\Model\Bitcoin\BitcoinClusterModel;
use App\Model\Bitcoin\BitcoinTagModel;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\Litecoin\LitecoinAddressModel;
use App\Model\Litecoin\LitecoinBlockModel;
use App\Model\Litecoin\LitecoinClusterModel;
use App\Model\Litecoin\LitecoinTagModel;
use App\Model\Litecoin\LitecoinTransactionModel;
use Underscore\Types\Arrays;

abstract class CurrencyType{
    const BITCOIN = 1;
    const LITECOIN = 2;


    private static $initiated=false;

    protected static $enabledCurrencies = [
        self::BITCOIN,
        self::LITECOIN,
    ];

    protected static $currenciesNames = [
        self::BITCOIN   => 'Bitcoin',
        self::LITECOIN  => 'Litecoin',
    ];

    protected static $currencyUnits = [
        self::BITCOIN   => 'BTC',
        self::LITECOIN  => 'LTC',
    ];

    protected static $blockModels;
    protected static $addressModels;
    protected static $transactionModels;
    protected static $clusterModels;
    protected static $tagModels;

    private static function init()
    {
        self::$blockModels = [
            CurrencyType::BITCOIN   => BitcoinBlockModel::getInstance(),
            CurrencyType::LITECOIN  => LitecoinBlockModel::getInstance(),
        ];

        self::$addressModels = [
            CurrencyType::BITCOIN   => BitcoinAddressModel::getInstance(),
            CurrencyType::LITECOIN  => LitecoinAddressModel::getInstance(),
        ];

        self::$transactionModels = [
            CurrencyType::BITCOIN   =>  BitcoinTransactionModel::getInstance(),
            CurrencyType::LITECOIN  =>  LitecoinTransactionModel::getInstance(),
        ];

        self::$clusterModels = [
            CurrencyType::BITCOIN   => BitcoinClusterModel::getInstance(),
            CurrencyType::LITECOIN  => LitecoinClusterModel::getInstance()
        ];

        self::$tagModels = [
            CurrencyType::BITCOIN   => BitcoinTagModel::getInstance(),
            CurrencyType::LITECOIN  => LitecoinTagModel::getInstance()
        ];

        self::$initiated=true;
    }


    /**
     * @param $currencyType
     * @return BitcoinBlockModel
     */
    public static function blockModel($currencyType)
    {
        if (!self::$initiated){
            self::init();
        }

        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return self::$blockModels[$currencyType];
    }

    /**
     * @param $currencyType
     * @return BitcoinAddressModel
     */
    public static function addressModel($currencyType)
    {
        if (!self::$initiated){
            self::init();
        }

        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$addressModels, $currencyType);
    }

    /**
     * @param $currencyType
     * @return BitcoinTransactionModel
     */
    public static function transactionModel($currencyType)
    {
        if (!self::$initiated){
            self::init();
        }

        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$transactionModels, $currencyType);
    }

    /**
     * @param $currencyType
     * @return BitcoinClusterModel
     */
    public static function clusterModel($currencyType)
    {
        if (!self::$initiated){
            self::init();
        }

        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$clusterModels, $currencyType);
    }

    /**
     * @param $currencyType
     * @return BitcoinTagModel
     */
    public static function tagModel($currencyType)
    {
        if (!self::$initiated){
        self::init();
        }

        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$tagModels, $currencyType);
    }

    public static function collectionName($model)
    {
        return $model::$collection;
    }

    public static function fromStr($strType){
        switch($strType){
            case 'bitcoin':
                return self::BITCOIN;
                break;
            case 'litecoin':
                return self::LITECOIN;
                break;
            default:
                throw new \Exception('Invalid cryptocurrency');
        }
    }

    public static function currencyUnit($currencyType)
    {
        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$currencyUnits, $currencyType);
    }

    public static function currencyTitle($currencyType)
    {
        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$currenciesNames, $currencyType);
    }
}

