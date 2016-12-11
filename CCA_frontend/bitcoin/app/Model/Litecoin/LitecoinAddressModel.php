<?php
namespace App\Model\Litecoin;

use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\CurrencyType;

class LitecoinAddressModel extends BitcoinAddressModel{
    protected static $type = CurrencyType::LITECOIN;
    protected static $database = 'litecoin_analysis';
}