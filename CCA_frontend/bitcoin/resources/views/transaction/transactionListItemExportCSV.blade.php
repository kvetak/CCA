<?php
    use Underscore\Types\Arrays;
    use \App\Model\CurrencyType;
    $currency = 'bitcoin';

$inputs = $transaction->getInputs();
$outputs = $transaction->getOutputs();

?>
<?php echo("\nTx time;");?>{{\Carbon\Carbon::createFromTimestamp($transaction->getTime())}}
<?php echo("TxID;");?>{{$transaction->getTxid()}}
<?php echo("Sum of Inputs;");?>{{$transaction->getSumOfInputs()}} {{CurrencyType::currencyUnit($currency)}}
<?php echo("Sum of Outputs;");?>{{$transaction->getSumOfOutputs()}} {{CurrencyType::currencyUnit($currency)}}
<?php echo("Fees;");?>{{$transaction->getSumOfFees()}} {{CurrencyType::currencyUnit($currency)}}
<?php echo("Num of Inputs;");?>@if( $transaction->isCoinbase() )
Coinbase @else{{$transaction->getUniqueInputAddresses()}}
<?php echo("Inputs;\n");?>
@foreach( $inputs  as $vin)
{{$vin->getSerializedAddress()}};{{$vin->getValue()}} {{CurrencyType::currencyUnit($currency)}}
@endforeach
@endif
<?php echo("Num of Outputs;");?>{{Arrays::size($outputs)}}
<?php echo("Outputs;\n");?>
@foreach($outputs as $vout)
{{$vout->getSerializedAddress()}};{{$vout->getValue()}} {{CurrencyType::currencyUnit($currency)}}
@endforeach
<?php echo("\n");?>