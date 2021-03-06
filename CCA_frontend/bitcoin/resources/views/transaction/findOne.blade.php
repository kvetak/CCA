<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Transaction: <small>{{$transaction['txid']}}</small></h1>
        <div class="alert {{$isTransactionConfirmed ? 'alert-success' : 'alert-danger'}}" role="alert">{{$transactionConfirmationMessage}}</div>
    </div>
    <div class="well transaction-data">
        @include('transaction.structure')
    </div>
    <div class="row">
        <div class="col-md-8">
            <table class="table table-striped">
                <caption>Transaction summary</caption>
                <tr>
                    <td><strong>Transaction</strong></td>
                    <td>{{$transaction['txid']}}</td>
                </tr>
                <tr>
                    <td><strong>Block</strong></td>
                    <td><a href="{{route('block_findone', ['hash' => $transaction['blockhash'], 'currency' => $currency])}}">{{ $transaction['blockhash'] }}</a></td>
                </tr>
                <tr>
                    <td><strong>Time/Blocktime</strong></td>
                    <td>{{\Carbon\Carbon::createFromTimestamp($transaction['time'])}} / {{\Carbon\Carbon::createFromTimestamp($transaction['blocktime'])}}</td>
                </tr>
                <tr>
                    <td><strong>Confirmations</strong></td>
                    <td>{{$confirmations}}</td>
                </tr>
                <tr>
                    <td><strong>Visualization</strong></td>
                    <td><a href="{{route('transaction_visualize', ['txid' => $transaction['txid'], 'currency'=>$currency])}}">Transaction graph</a></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="clearfix"></div>
@stop