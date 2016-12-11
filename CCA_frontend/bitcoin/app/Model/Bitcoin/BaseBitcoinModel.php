<?php
namespace App\Model\Bitcoin;
use App\Model\BaseCurrencyModel;
use App\Model\CurrencyType;


/**
 * Zakladny model pre implementaciu prace s kryptomenou Bitcoin.
 * Class BaseBitcoinModel
 * @package App\Model\Bitcoin
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class BaseBitcoinModel extends BaseCurrencyModel{
    /**
     * Nazov databaze.
     * @var string
     */
    protected static $database   = 'blockchain_analysis';
    /**
     * Nastavenie typu na Bitcoin.
     * @var int
     */
    protected static $type = CurrencyType::BITCOIN;

    protected function getAddressCollectionName()
    {
        return CurrencyType::collectionName(CurrencyType::addressModel(static::$type));
    }

    protected function getAddressModelName()
    {
        return CurrencyType::addressModel(static::$type);
    }

    protected function getTransactionModelName()
    {
        return CurrencyType::transactionModel(static::$type);
    }

    protected function getClusterModelName()
    {
        return CurrencyType::clusterModel(static::$type);
    }
}