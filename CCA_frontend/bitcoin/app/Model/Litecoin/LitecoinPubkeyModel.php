<?php

namespace App\Model\Litecoin;


use App\Model\Bitcoin\BitcoinPubkeyModel;
use App\Model\CurrencyType;

class LitecoinPubkeyModel extends BitcoinPubkeyModel
{
    protected function getType()
    {
        return CurrencyType::LITECOIN;
    }
}