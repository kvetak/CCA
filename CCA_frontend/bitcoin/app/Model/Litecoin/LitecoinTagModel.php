<?php

namespace App\Model\Litecoin;


use App\Model\Bitcoin\BitcoinTagModel;
use App\Model\CurrencyType;

class LitecoinTagModel extends BitcoinTagModel
{
    protected function getType()
    {
        return CurrencyType::LITECOIN;
    }
}