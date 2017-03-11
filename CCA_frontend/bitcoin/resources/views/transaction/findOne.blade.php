<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Transaction: <small>{{$transactionDto->getTxid()}}</small></h1>
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
                    <td>{{$transactionDto->getTxid()}}</td>
                </tr>
                <tr>
                    <td><strong>Block</strong></td>
                    <td><a href="{{route('block_findone', ['hash' => $transactionDto->getBlockhash(), 'currency' => $currency])}}">{{ $transactionDto->getBlockhash()}}</a></td>
                </tr>
                <tr>
                    <td><strong>Time/Blocktime</strong></td>
                    <td>{{\Carbon\Carbon::createFromTimestamp($transactionDto->getBlocktime())}} / {{\Carbon\Carbon::createFromTimestamp($transactionDto->getBlocktime())}}</td>
                </tr>
                <tr>
                    <td><strong>Confirmations</strong></td>
                    <td>{{$confirmations}}</td>
                </tr>
                <tr>
                    <td><strong>Visualization</strong></td>
                    <td><a href="{{route('transaction_visualize', ['txid' => $transactionDto->getTxid(), 'currency'=>$currency])}}">Transaction graph</a></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="clearfix"></div>
@stop