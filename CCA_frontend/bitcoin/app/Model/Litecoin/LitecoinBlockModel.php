<?php
namespace App\Model\Litecoin;

use App\Model\Bitcoin\BitcoinBlockModel;
use App\Model\CurrencyType;

class LitecoinBlockModel extends BitcoinBlockModel{
    protected function getType()
    {
        return CurrencyType::LITECOIN;
    }
}