<?php
namespace App\Model\Factory;

use App\Model\CurrencyType;
use Illuminate\Support\Arr;
use Underscore\Types\Arrays;

/**
*
* @author Tomas Drozda <tomas.drozda@icloud.com>
*/
class Factory
{
    protected static $addressModels = [
        CurrencyType::BITCOIN => '',
    ];

    protected static $transactionModels = [
        CurrencyType::BITCOIN => '',
    ];

    protected static $clusterModels = [
        CurrencyType::BITCOIN => '',
    ];

    public static function addressModel($currencyType)
    {
        return Arrays::get(self::$addressModels, $currencyType);
    }

    public static function transactionModel($currencyType)
    {
        return Arrays::get(self::$transactionModels, $currencyType);
    }

    public static function clusterModel($currencyType)
    {
        return Arrays::get(self::$clusterModels, $currencyType);
    }


    public static function createClusterModel($currencyType, $parameters)
    {

    }

    public static function createBlockModel($currencyType)
    {

    }
}