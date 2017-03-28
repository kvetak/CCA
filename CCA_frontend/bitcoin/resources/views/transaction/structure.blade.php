<?php
    use \App\Model\CurrencyType;
?>
<div class="vertical-align">
    <div class="col-md-2">
        @if(!isset($displayOnlyHeader) || $displayOnlyHeader)
            <h5>Transaction structure:</h5>
        @endif
    </div>
</div>
<div class="vertical-align">
    <div class="col-md-5">
        <ul style="padding: 0;">
            @if($transactionDto->isCoinbase())
                <li>Coinbase transaction without inputs</li>
            @else
                @foreach( $transactionDto->getInputs()  as $vin)
                <?php
                      $address = $vin->getSerializedAddress();
                ?>
                <li><a href="{{route('transaction_findone',['txid'=>  $vin->getTxid(), 'currency' => $currency])}}" class="glyphicon glyphicon-arrow-left"/>&nbsp;
                    <a href="{{route('address_findone',['address'=> $address, 'currency' => $currency])}}">{{$address}}</a>
                    <small> ({{$vin->getValue()}} {{CurrencyType::currencyUnit($currency)}})</small></li>
                @endforeach
            @endif
        </ul>
    </div>
    <div class="col-xs-1">
        <span class="glyphicon glyphicon-chevron-right"></span>
    </div>
    <div class="col-md-5">
        <ul class="center-block" style="padding: 0;">
            @foreach($transactionDto->getOutputs() as $vout)
            <li>
                <?php
                    $address=$vout->getSerializedAddress();
                ?>
                <a href="{{route('address_findone',['address'=> $address, 'currency' => $currency])}}">{{$address}}
                </a> <small>({{$vout->getValue()}} {{CurrencyType::currencyUnit($currency)}})</small>
                @if($vout->isSpent())
                    <a href="{{route('transaction_findone', ['txid' =>$vout->getSpentTxid(), 'currency' => $currency])}}" class="glyphicon glyphicon-arrow-right"></a>
                @else
                    <span style="color:red;">unspend</span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-5 col-md-offset-2">
    @if( !$transactionDto->isCoinbase())
        Unique input addresses: {{$transactionDto->getUniqueInputAddresses()}}
    @endif
</div>
</div>
<div class="row">
    <div class="col-md-4" style="padding: 0;">
        <div class="btn btn-default btn-info"><strong>Fees:</strong> {{$transactionDto->getSumOfFees()}} {{CurrencyType::currencyUnit($currency)}}</div>
    </div>
    <div class="col-md-8 text-right">
        <div role="group" aria-label="...">
        <div class="btn btn-default btn-primary"><strong>Input:</strong> <span style="font-size:0.9em;">{{$transactionDto->getSumOfInputs()}} {{CurrencyType::currencyUnit($currency)}}</span></div>
        <div class="btn btn-success"><strong>Output:</strong> <span style="font-size:0.9em;">{{$transactionDto->getSumOfOutputs()}} {{CurrencyType::currencyUnit($currency)}}</span></div>
        </div>
    </div>
</div>