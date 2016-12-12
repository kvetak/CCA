<?php
namespace App\Model;

abstract class BaseCurrencyModel extends BaseNeoModel {
    /**
     * Vrací typ kryptoměny se kterou model pracuje
     * @return mixed
     */
    abstract protected function getType();

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