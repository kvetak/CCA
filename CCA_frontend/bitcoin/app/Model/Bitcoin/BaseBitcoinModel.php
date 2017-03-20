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

    /**
     * Serializace hodnoty tak, aby se dala uložit v databázi
     * @param $val mixed Hodnota k zakódování
     * @return string Serializovaná hodnota
     */
    protected static function input_output_encode($val)
    {
        return base64_encode(serialize($val));
    }

    /**
     * Deserializace hodnoty, která byla uložena v databázi
     * @param $val string - Serializovaná hodnota
     * @return mixed Deserializovaná hodnota
     */
    protected static function input_output_decode($val)
    {
        return unserialize(base64_decode($val));
    }
}