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
                            <td>{{$addressDto->getTransactionsCount()}}</td>
                        </tr>
                        <tr>
                            <th>Last transaction:</th>
                            <td>
                                {{\Carbon\Carbon::createFromTimestamp(end($transactions)->getTime())}}
                            </td>
                        </tr>
                        <tr>
                            <th>Tools</th>
                            <td>
                                <a href="{{route('address_cluster', ['address' => $addressDto->getAddress(), 'currency'=>$currency])}}">Show addresses with same owner</a>
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
                        @if(count($addressDto->getTags()))
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tag</th>
                                        <th>Url</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($addressDto->getTags() as $tag)
                                    <tr>
                                        <td>{{$tag['tag']}}</td>
                                        <td><a href="{{$tag['url']}}" target="_blank">{{$tag['url']}}</a></td>
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
        @each('transaction.transactionListItem', $transactions, 'transaction')
    </div>
    <div class="clearfix center-"></div>
    <div class="row text-center">
        {{$pagination}}
    </div>
@stop