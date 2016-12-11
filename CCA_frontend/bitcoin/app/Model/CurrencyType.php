<?php
namespace App\Model;

use Underscore\Types\Arrays;

class CurrencyType{
    const BITCOIN = 1;
    const LITECOIN = 2;

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

    protected static $blockModels = [
        CurrencyType::BITCOIN   => 'App\Model\Bitcoin\BitcoinBlockModel',
        CurrencyType::LITECOIN  => 'App\Model\Litecoin\LitecoinBlockModel',
    ];

    protected static $addressModels = [
        CurrencyType::BITCOIN   => 'App\Model\Bitcoin\BitcoinAddressModel',
        CurrencyType::LITECOIN  => 'App\Model\Litecoin\LitecoinAddressModel',
    ];

    protected static $transactionModels = [
        CurrencyType::BITCOIN   => 'App\Model\Bitcoin\BitcoinTransactionModel',
        CurrencyType::LITECOIN  =>  'App\Model\Litecoin\LitecoinTransactionModel',
    ];

    protected static $clusterModels = [
        CurrencyType::BITCOIN   => 'App\Model\Bitcoin\BitcoinClusterModel',
        CurrencyType::LITECOIN  => 'App\Model\Litecoin\LitecoinClusterModel',
    ];

    public static function blockModel($currencyType)
    {
        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$blockModels, $currencyType);
    }

    public static function addressModel($currencyType)
    {
        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$addressModels, $currencyType);
    }

    public static function transactionModel($currencyType)
    {
        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$transactionModels, $currencyType);
    }

    public static function clusterModel($currencyType)
    {
        if( ! is_int($currencyType)){
            $currencyType = self::fromStr($currencyType);
        }
        return Arrays::get(self::$clusterModels, $currencyType);
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

