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
abstract class BaseBitcoinModel extends BaseCurrencyModel
{
    /**
     * Nastavenie typu na Bitcoin.
     */
    const type = CurrencyType::BITCOIN;
    protected function getType()
    {
        return self::type;
    }
}