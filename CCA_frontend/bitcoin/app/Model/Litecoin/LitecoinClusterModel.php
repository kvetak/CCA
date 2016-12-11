<?php
namespace App\Model\Litecoin;

use App\Model\Bitcoin\BitcoinClusterModel;
use App\Model\CurrencyType;

class LitecoinClusterModel extends BitcoinClusterModel{
    protected static $type = CurrencyType::LITECOIN;
    protected static $database = 'litecoin_analysis';
}