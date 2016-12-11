<?php
namespace App\Model\Litecoin;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\CurrencyType;

class LitecoinTransactionModel extends BitcoinTransactionModel{
    protected static $type      = CurrencyType::LITECOIN;
    protected static $database  = 'litecoin_analysis';
}