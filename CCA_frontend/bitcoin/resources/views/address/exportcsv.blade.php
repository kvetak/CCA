<?php
    use \App\Model\CurrencyType;
?>

<?php echo("Address;");?>{{$addressDto->getAddress()}}
<?php echo("Balance;");?>{{$addressDto->getBalance(False, True)}} {{CurrencyType::currencyUnit($currency)}}
<?php echo("Num of Transactions;");?>{{count($transactions)}}
<?php echo "\n"; ?>
@each('transaction.transactionListItemExportCSV', $transactions, 'transaction')

