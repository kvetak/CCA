<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>{{CurrencyType::currencyTitle($currency)}} blocks</h1>
    </div>
    <div class="clearfix"></div>
    <table class="table table-striped">
    <thead>
        <tr>
            <th>Height</th>
            <th>Hash</th>
            <th>Time</th>
            <th>Transactions</th>
            <th>Sum of outputs</th>
        </tr>
    </thead>
    <tbody>
    @foreach($blocks as $block)
        <tr>
            <td><a href="{{route('block_findone',['hash'=>$block->getHash(), 'currency' => $currency])}}">{{$block->getHeight()}}</a></td>
            <td><small><a href="{{route('block_findone',['hash'=>$block->getHash(), 'currency' => $currency])}}">{{$block->getHash()}}</a></small></td>
            <td>{{\Carbon\Carbon::createFromTimestamp($block->getTime())}}</td>
            <td>{{$block->getTransactionsCount()}}</td>
            <td>{{$block->getSumOfOutputs()}} {{CurrencyType::currencyUnit($currency)}}</td>
        </tr>
    @endforeach
    </tbody>
    </table>
    <div class="clearfix center-"></div>
    <div class="row text-center">
            {{$pagination}}
    </div>
@stop