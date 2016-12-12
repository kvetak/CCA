<?php
namespace App\Model\Litecoin;

use App\Model\Bitcoin\BitcoinClusterModel;
use App\Model\CurrencyType;

class LitecoinClusterModel extends BitcoinClusterModel{
    protected function getType()
    {
        return CurrencyType::LITECOIN;
    }
}