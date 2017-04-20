<?php
    use Underscore\Types\Arrays;
    use \App\Model\CurrencyType;
    $currency = 'bitcoin';

$inputs = $transaction->getInputs();
$outputs = $transaction->getOutputs();

?>
<div class="transaction">

        <div class="row transaction-title">
            <div style="" class="vertical-align">
                <div class="col-md-2">
                    <strong>Transaction time: </strong>{{\Carbon\Carbon::createFromTimestamp($transaction->getTime())}}
                </div>
                <div class="col-md-5">
                    <strong>Transaction ID: </strong><a href="{{route('transaction_findone',['txid'=>$transaction->getTxid(), 'currency' => $currency])}}">{{$transaction->getTxid()}}</a>
                </div>
                <div class="col-md-3">
                    <div class="btn btn-default btn-primary"><strong>Input:</strong> <span style="font-size:0.9em;">{{$transaction->getSumOfInputs()}} {{CurrencyType::currencyUnit($currency)}}</span></div>
                    <div class="btn btn-success"><strong>Output:</strong> <span style="font-size:0.9em;">{{$transaction->getSumOfOutputs()}} {{CurrencyType::currencyUnit($currency)}}</span></div>
                    <div class="btn btn-default btn-info"><strong>Fees:</strong> {{$transaction->getSumOfFees()}} {{CurrencyType::currencyUnit($currency)}}</div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>

    <div class="vertical-align">
        <div class="col-md-5">
            @if( $transaction->isCoinbase() )
                <div><strong>Coinbase transaction without inputs</strong></div>
            @else
                <div><strong>Unique input addresses: </strong>{{$transaction->getUniqueInputAddresses()}}</div>
                <strong>Inputs:</strong>
                <ul style="padding: 0;">
                    @foreach( $inputs  as $vin)
                        <li>
                            <a href="{{route('address_findone',['address'=> $vin->getSerializedAddress(), 'currency' => $currency])}}">{{$vin->getSerializedAddress()}}</a>
                            <small> ({{$vin->getValue()}} {{CurrencyType::currencyUnit($currency)}})</small>
                        </li>
                    @endforeach
                </ul>

            @endif
        </div>
        <div class="col-xs-1">
            <span class="glyphicon glyphicon-chevron-right"></span>
        </div>
        <div class="col-md-5">
                <div><strong>Unique outputs addresses: </strong>{{Arrays::size($outputs)}}</div>

            <strong>Outputs:</strong>
            <ul class="center-block" style="padding: 0;">
                @foreach($outputs as $vout)
                    <li>
                        <a href="{{route('address_findone',['address'=> $vout->getSerializedAddress(), 'currency' => $currency])}}">{{$vout->getSerializedAddress()}}
                        </a> <small>({{$vout->getValue()}} {{CurrencyType::currencyUnit($currency)}})</small>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>


    <hr/>

</div>