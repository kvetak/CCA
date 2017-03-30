<?php

?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Transaction: <small>{{$txid}}</small></h1>
        <h2>output number: <small>{{$outputNo}} </small></h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Output detail</h3>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <td><strong>Value</strong></td>
                            <td>{{$outputDto->getValue()}}</td>
                        </tr>
                        <tr>
                            <td><strong>Output address</strong></td>
                            <td>{{$outputDto->getSerializedAddress()}}</td>
                        </tr>
                        <tr>
                            <td><strong>ScriptPubkey</strong></td>
                            <td style="word-break:break-all;">{{bin2hex($outputDto->getScriptPubkey())}}</td>
                        </tr>
                        <tr>
                            <td><strong>Spent?</strong></td>
                            <td>{{$outputDto->isSpent() ? "Yes" : "No"}}</td>
                        </tr>

                        @if($outputDto->isSpent())
                            <tr>
                                <td><strong>Spent in transaction</strong></td>
                                <td><a href="{{route('transaction_findone', ['txid' => $outputDto->getSpentTxid(), 'currency' => $currency])}}">{{$outputDto->getSpentTxid()}}</a></td>
                            </tr>
                            <tr>
                                <td><strong>Spent time</strong></td>
                                <td>{{\Carbon\Carbon::createFromTimestamp($outputDto->getSpentTs())}}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include("transaction.outputRedeem", array('redeemerDto' => $outputDto->getRedeemerDto()))

    <div class="clearfix"></div>
@stop