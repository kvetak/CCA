<?php
namespace App\Model;

abstract class BaseCurrencyModel extends BaseNeoModel
{
    /**
     * Vrací typ kryptoměny se kterou model pracuje
     * @return mixed
     */
    abstract protected function getType();

    /**
     * @return string - jméno uzlů použitých v daném modelu
     */
    protected abstract function getNodeName();

    /**
     *
     * @return string
     */
    protected function getEffectiveNodeName($node_name=null)
    {
        if ($node_name == null){
            return CurrencyType::currencyUnit($this->getType())."_".$this->getNodeName();
        }
        return CurrencyType::currencyUnit($this->getType())."_".$node_name;
    }
}