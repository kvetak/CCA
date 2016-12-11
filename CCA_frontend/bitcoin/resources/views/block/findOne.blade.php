<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>{{CurrencyType::currencyTitle($currency)}} block: <small>{{$block['hash']}}</small></h1>
        <div class="alert {{$isBlockConfirmed ? 'alert-success' : 'alert-danger'}}" role="alert">{{$blockConfirmationMessage}}</div>
    </div>
    <div class="row vertical-align">
    <div class="col-md-2">
        <nav>
            <ul class="pager">
                <li class="previous"><a href="{!! route('block_findone', ['hash' => $block['previousblockhash'], 'currency' => $currency]) !!}"><span aria-hidden="true">&larr;</span> Previous block</a></li>
            </ul>
        </nav>
    </div>
    <div class="col-md-8">
        <table class="table table-striped">
            <caption>Summary</caption>
            <tr>
                <td><strong>Hash</strong></td>
                <td><small>{{$block['hash']}}</small></td>
            </tr>
            <tr>
                <td><strong>Next block</strong></td>
                <td><small><a href="{!! route('block_findone',['hash' => $block['nextblockhash'], 'currency' => $currency])!!}">{!! $block['nextblockhash'] !!}</a></small></td>
            </tr>
            <tr>
                <td><strong>Previous block</strong></td>
                <td><small><a href="{!! route('block_findone', ['hash' => $block['previousblockhash'], 'currency' => $currency]) !!}">{{$block['previousblockhash']}}</a></small></td>
            </tr>
            <tr>
                <td><strong>Height</strong></td>
                <td>{{$block['height']}}</td>
            </tr>
            <tr>
                <td><strong>Time</strong></td>
                <td>{{\Carbon\Carbon::createFromTimestamp($block['time'])}}</td>
            </tr>
            <tr>
                <td><strong>Sum of inputs</strong></td>
                <td>{{$block['sum_of_inputs']}} {{CurrencyType::currencyUnit($currency)}}</td>
            </tr>
            <tr>
                <td><strong>Sum of outputs</strong></td>
                <td>{{$block['sum_of_outputs']}} {{CurrencyType::currencyUnit($currency)}}</td>
            </tr>
            <tr>
                <td><strong>Sum of fees</strong></td>
                <td>{{$block['sum_of_fees']}} {{CurrencyType::currencyUnit($currency)}}</td>
            </tr>
            <tr>
                <td><strong>Number of transactions</strong></td>
                <td>{{$block['transactions']}}</td>
            </tr>
            <tr>
                <td><strong>Confirmations</strong></td>
                <td>{{$confirmations}}</td>
            </tr>
        </table>
    </div>
    <div class="col-md-2">
        <nav>
            <ul class="pager">
                <li class="next"><a href="{!! route('block_findone',['hash' => $block['nextblockhash'], 'currency' => $currency])!!}">Next block <span aria-hidden="true">&rarr;</span></a></li>
            </ul>
        </nav>
    </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <h2>Transactions</h2>
        @each('transaction.transactionListItem', $transactions,'transaction')
    </div>
    <div class="clearfix center-"></div>
    <div class="row text-center">
        {{$pagination}}
    </div>
@stop