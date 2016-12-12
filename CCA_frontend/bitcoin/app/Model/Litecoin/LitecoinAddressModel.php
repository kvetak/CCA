<?php
namespace App\Model\Litecoin;

use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\CurrencyType;

class LitecoinAddressModel extends BitcoinAddressModel
{
    protected function getType()
    {
        return CurrencyType::LITECOIN;
    }
}