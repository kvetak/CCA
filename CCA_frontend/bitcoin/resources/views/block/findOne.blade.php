<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>{{CurrencyType::currencyTitle($currency)}} block: <small>{{$block->getHash()}}</small></h1>
        <div class="alert {{$isBlockConfirmed ? 'alert-success' : 'alert-danger'}}" role="alert">{{$blockConfirmationMessage}}</div>
    </div>
    <div class="row vertical-align">
    <div class="col-md-2">
        <nav>
            <ul class="pager">
                @if($block->getHeight() > 0)
                    <li class="previous"><a href="{!! route('block_findone', ['hash' => $block->getPreviousBlockHash(), 'currency' => $currency]) !!}"><span aria-hidden="true">&larr;</span> Previous block</a></li>
                @endif
            </ul>
        </nav>
    </div>
    <div class="col-md-8">
        <table class="table table-striped">
            <caption>Summary</caption>
            <tr>
                <td><strong>Hash</strong></td>
                <td><small>{{$block->getHash()}}</small></td>
            </tr>
            <tr>
                <td><strong>Next block</strong></td>
                <td><small><a href="{!! route('block_findone',['hash' => $block->getNextBlockHash(), 'currency' => $currency])!!}">{!! $block->getNextBlockHash() !!}</a></small></td>
            </tr>
            <tr>
                <td><strong>Previous block</strong></td>
                @if($block->getHeight() > 0)
                    <td><small><a href="{!! route('block_findone', ['hash' => $block->getPreviousBlockHash(), 'currency' => $currency]) !!}">{{$block->getPreviousBlockHash()}}</a></small></td>
                @else
                    <td><small>{{$block->getPreviousBlockHash()}}</small></td>
                @endif
            </tr>
            <tr>
                <td><strong>Height</strong></td>
                <td>{{$block->getHeight()}}</td>
            </tr>
            <tr>
                <td><strong>Time</strong></td>
                <td>{{\Carbon\Carbon::createFromTimestamp($block->getTime())}}</td>
            </tr>
            <tr>
                <td><strong>Sum of inputs</strong></td>
                <td>{{$block->getSumOfInputs()}} {{CurrencyType::currencyUnit($currency)}}</td>
            </tr>
            <tr>
                <td><strong>Sum of outputs</strong></td>
                <td>{{$block->getSumOfOutputs()}} {{CurrencyType::currencyUnit($currency)}}</td>
            </tr>
            <tr>
                <td><strong>Sum of fees</strong></td>
                <td>{{$block->getSumOfFees()}} {{CurrencyType::currencyUnit($currency)}}</td>
            </tr>
            <tr>
                <td><strong>Number of transactions</strong></td>
                <td>{{$block->getTransactionsCount()}}</td>
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
                <li class="next"><a href="{!! route('block_findone',['hash' => $block->getNextBlockHash(), 'currency' => $currency])!!}">Next block <span aria-hidden="true">&rarr;</span></a></li>
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