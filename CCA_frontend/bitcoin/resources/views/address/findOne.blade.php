<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Address: <small>{{$addressDto->getAddress()}}</small></h1>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Summary</h3>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tbody>
                        <tr>
                            <th>Balance</th>
                            <td>{{$addressDto->getBalance()}} {{CurrencyType::currencyUnit($currency)}}</td>
                        </tr>
                        <tr>
                            <th>Transactions</th>
                            <td>{{count($transactions)}}</td>
                        </tr>
                        <tr>
                            <th>Last transaction:</th>
                            <td>
                                @if(count($transactions) > 0)
                                    {{\Carbon\Carbon::createFromTimestamp(end($transactions)->getTime())}}
                                @else
                                    Never
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Public key</th>
                            <td style="word-break:break-all;">
                                @if($publicKeyDto == null)
                                    Unknown
                                @else
                                    <a href="{{route('pubkey_findOne', ['currency' => $currency, 'pubkey' => $publicKeyDto->getCompressedPubkey()])}}">{{$publicKeyDto->getCompressedPubkey()}}</a>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tools</th>
                            <td>
                                <p><a href="{{route('address_cluster', ['address' => $addressDto->getAddress(), 'currency'=>$currency])}}">Show addresses with same owner</a></p>
                                <p><a href="{{route('address_export', ['address' => $addressDto->getAddress(), 'currency'=>$currency])}}">Export transactions to rich-text</a></p>
                                <p><a href="{{route('address_exportcsv', ['address' => $addressDto->getAddress(), 'currency'=>$currency])}}">Export transactions to CSV</a></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Identity</h3>
                    </div>
                    <div class="panel-body">
                        @if(count($tags))
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tag</th>
                                        <th>Url</th>
                                        <th>Tag detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($tags as $tag)
                                    <tr>
                                        <td>{{$tag->getTag()}}</td>
                                        <td><a href="{{$tag->getUrl()}}" target="_blank">{{$tag->getUrl()}}</a></td>
                                        <td><a href="{{route('tag_findOne', ['tagId' => $tag->getId(), 'currency' => $currency])}}">detail</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @else
                            Identity not known!
                        @endif
                    </div>
            </div>
        </div>
    </div>
    <div class="row">
        <h2>Transactions</h2>
        @foreach($transactions as $transaction)
            <div class="transaction">
                @if(!isset($displayOnlyHeader) || $displayOnlyHeader)
                    <div class="row transaction-title">
                        <div style="" class="vertical-align">
                            <div class="col-md-2">
                                {{\Carbon\Carbon::createFromTimestamp($transaction->getTime())}}
                            </div>
                            <div class="col-md-5">
                                <strong><small><a href="{{route('transaction_findone',['txid'=>$transaction->getTxid(), 'currency' => $currency])}}">{{$transaction->getTxid()}}</a></small></strong>
                            </div>
                            <div class="col-md-3">
                                {{$balanceChanges[$transaction->getTxid()]}} {{CurrencyType::currencyUnit($currency)}}
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
        @endforeach
    </div>
    <div class="clearfix center-"></div>
    <div class="row text-center">
        {{$pagination}}
    </div>
@stop