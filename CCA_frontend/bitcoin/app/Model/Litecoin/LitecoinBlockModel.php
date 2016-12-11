<?php
namespace App\Model\Litecoin;

use App\Model\Bitcoin\BitcoinBlockModel;
use App\Model\CurrencyType;

class LitecoinBlockModel extends BitcoinBlockModel{
    protected static $type = CurrencyType::LITECOIN;
    protected static $database = 'litecoin_analysis';
}