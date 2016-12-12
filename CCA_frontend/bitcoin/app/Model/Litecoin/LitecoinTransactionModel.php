<?php
namespace App\Model\Litecoin;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\CurrencyType;

class LitecoinTransactionModel extends BitcoinTransactionModel{
    protected function getType()
    {
        return CurrencyType::LITECOIN;
    }
}