<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Address: <small>{{$address->getAddress()}}</small></h1>
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
                            <td>{{$address->getBalance(False, True)}} {{CurrencyType::currencyUnit($currency)}}</td>
                        </tr>
                        <tr>
                            <th>Transactions</th>
                            <td>{{$address->getTransactionsCount()}}</td>
                        </tr>
                        <tr>
                            <th>Last transaction:</th>
                            <td>
                                {{\Carbon\Carbon::createFromTimestamp($transactions->next()['time'])}}
                            </td>
                        </tr>
                        <tr>
                            <th>Tools</th>
                            <td>
                                <a href="{{route('address_cluster', ['address' => $address->getAddress(), 'currency'=>$currency])}}">Show addresses with same owner</a>
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
                        @if(count($address->getTags()))
                            <table class="table">
                                <thead>
                                    <th>Tag</th>
                                    <th>Url</th>
                                </thead>
                                <tbody>
                                @foreach($address->getTags() as $tag)
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