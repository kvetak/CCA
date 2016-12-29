<?php
    use \App\Model\CurrencyType;
?>
<div class="transaction">
    @if(!isset($displayOnlyHeader) || $displayOnlyHeader)
        <div class="row transaction-title">
            <div style="" class="vertical-align">
                <div class="col-md-2">
                    {{\Carbon\Carbon::createFromTimestamp($transaction->getBlocktime())}}
                </div>
                <div class="col-md-5">
                    <strong><small><a href="{{route('transaction_findone',['txid'=>$transaction->getTxid(), 'currency' => $currency])}}">{{$transaction->getTxid()}}</a></small></strong>
                </div>
                <div class="col-md-3">
                    {{$transaction->getSumOfOutputs()}} {{CurrencyType::currencyUnit($currency)}}
                </div>
                <div class="col-md-offset-1 col-md-1">
                    <a class="collapse-btn btn btn-primary" role="button" data-toggle="collapse" href="#transaction-{{$transaction->getTxid()}}" aria-expanded="false" aria-controls="transaction-{{$transaction->getTxid()}}">
                        Detail
                    </a>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    @else
        <div class="row">
            <h4>Structure</h4>
        </div>
    @endif
    <div class="{{ (!isset($displayOnlyHeader) || $displayOnlyHeader)? "collapse" : "" }} row well transaction-data" data-txid="{{$transaction->getTxid()}}" id="transaction-{{$transaction->getTxid()}}" style="margin-top:10px;">

    </div>
</div>