<?php
namespace App\Model;

abstract class BaseCurrencyModel extends BaseNeoModel {
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
    protected function getEffectiveNodeName()
    {
        return CurrencyType::currencyUnit($this->getType())."_".$this->getNodeName();
    }

    protected function getAddressCollectionName()
    {
        return CurrencyType::collectionName(CurrencyType::addressModel($this->getType()));
    }

    protected function getAddressModelName()
    {
        return CurrencyType::addressModel($this->getType());
    }

    protected function getTransactionModelName()
    {
        return CurrencyType::transactionModel($this->getType());
    }

    protected function getClusterModelName()
    {
        return CurrencyType::clusterModel($this->getType());
    }
}