<?php
    use Underscore\Types\Arrays;
    use \App\Model\CurrencyType;

    $inputs = Arrays::filterBy($transaction['inputsOutputs'], 'type', \App\Model\InputsOutputsType::TYPE_INPUT);
    $outputs = Arrays::filterBy($transaction['inputsOutputs'], 'type', \App\Model\InputsOutputsType::TYPE_OUTPUT);
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
        @foreach( $inputs  as $vin)
            @if(isset($vin['coinbase']))
                <li>Coinbase transaction without inputs</li>
            @else
                <?php
                        $address    = $vin['addresses'][0];
                        $tag        = Arrays::get(Arrays::filterBy($tags, 'address', $address), '0.tags.0.tag', null);
                        $tagUrl     = Arrays::get(Arrays::filterBy($tags, 'address', $address), '0.tags.0.url', null);
                ?>
                <li><a href="{{route('transaction_findone',['txid'=>$vin['txid'], 'currency' => $currency])}}" class="glyphicon glyphicon-arrow-left"/>&nbsp;
                    @if( ! empty($tag) )
                        <a href="{{route('address_findone',['address'=> $address, 'currency' => $currency])}}">{{str_limit($address, 15)}}
                            @if(!empty($tagUrl))
                                <a href="{{$tagUrl}}" target="_blank"><strong>{{$tag}}</strong></a>
                            @else
                                {{$tag}}
                            @endif
                        </a>
                    @else
                        <a href="{{route('address_findone',['address'=> $address, 'currency' => $currency])}}">{{$address}}</a>
                    @endif
                    <small> ({{$vin['value']}} {{CurrencyType::currencyUnit($currency)}})</small></li>
            @endif
        @endforeach
        </ul>
    </div>
    <div class="col-xs-1">
        <span class="glyphicon glyphicon-chevron-right"></span>
    </div>
    <div class="col-md-5">
        <ul class="center-block" style="padding: 0;">
            @foreach($outputs as $vout)
            <li>
                <?php
                    $address = $vout['addresses'][0];
                    $tag        = Arrays::get(Arrays::filterBy($tags, 'address', $address), '0.tags.0.tag', null);
                    $tagUrl     = Arrays::get(Arrays::filterBy($tags, 'address', $address), '0.tags.0.url', null);
                ?>
                @if( ! empty($tag))
                        <a href="{{route('address_findone',['address'=> $address, 'currency' => $currency])}}">{{str_limit($address, 15)}}
                            @if(!empty($tagUrl))
                                <a href="{{$tagUrl}}" target="_blank"><strong>{{$tag}}</strong></a>
                            @else
                                {{$tag}}
                            @endif
                @else
                        <a href="{{route('address_findone',['address'=> $address, 'currency' => $currency])}}">{{$address}}
                @endif
                </a> <small>({{$vout['value']}} {{CurrencyType::currencyUnit($currency)}})</small>
                @if($vout['spent'])
                    <a href="{{route('transaction_findone', ['txid' =>$vout['spentTxid'], 'currency' => $currency])}}" class="glyphicon glyphicon-arrow-right"></a>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-5 col-md-offset-2">
    @if( ! isset($vin['coinbase']))
        Unique input addresses: {{$transaction['uniqueInputs']}}
    @endif
</div>
</div>
<div class="row">
    <div class="col-md-4" style="padding: 0;">
        <div class="btn btn-default btn-info"><strong>Fees:</strong> {{$transaction['sumOfFees']}} {{CurrencyType::currencyUnit($currency)}}</div>
    </div>
    <div class="col-md-8 text-right">
        <div role="group" aria-label="...">
        <div class="btn btn-default btn-primary"><strong>Input:</strong> <span style="font-size:0.9em;">{{$transaction['sumOfInputs']}} {{CurrencyType::currencyUnit($currency)}}</span></div>
        <div class="btn btn-success"><strong>Output:</strong> <span style="font-size:0.9em;">{{$transaction['sumOfOutputs']}} {{CurrencyType::currencyUnit($currency)}}</span></div>
        </div>
    </div>
</div>