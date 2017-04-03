<?php
    use App\Model\Bitcoin\ScriptParser\BaseScriptParser;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Transaction: <small>{{$txid}}</small></h1>
        <h2>Input number: <small>{{$inputNo}} </small></h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Input detail</h3>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <td><strong>Source transaction</strong></td>
                            <td><small><a href="{{route('transaction_findone',['txid'=> $inputDto->getTxid(), 'currency' => $currency])}}">{{$inputDto->getTxid()}}</a></small></td>
                        </tr>
                        <tr>
                            <td><strong>Source output</strong></td>
                            <td><small><a href="{{route('transaction_output', ['txid'=> $inputDto->getTxid(), 'currency' => $currency, 'outputNo' => $inputDto->getVout()])}}">{{$inputDto->getVout()}}</a></small></td>
                        </tr>
                        <tr>
                            <td><strong>Value</strong></td>
                            <td>{{$inputDto->getValue()}}</td>
                        </tr>
                        <tr>
                            <td><strong>Input address</strong></td>
                            <td><small>{{$inputDto->getSerializedAddress()}}</small></td>
                        </tr>
                        <tr>
                            <td><strong>ScriptSig</strong></td>
                            <td style="word-break:break-all;">{{bin2hex($inputDto->getScriptSig())}}</td>
                        </tr>
                        <tr>
                            <td><strong>Pubkey script present in input</strong></td>
                            <td style="word-break:break-all;">{{bin2hex($inputDto->getParsedScriptSig()->getPubkeyScript())}}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(count($inputDto->getParsedScriptSig()->getpubkeys()) > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Public keys present in ScriptSig</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        @foreach ($inputDto->getParsedScriptSig()->getpubkeys() as $pubkey)
                            <tr>
                                <td><small>{{$pubkey}}</small></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>

        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">ScriptSig Signatures</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <tr>
                            <th>R field</th>
                            <th>S field</th>
                            <th>Signature type</th>
                        </tr>
                        @foreach ($inputDto->getParsedScriptSig()->getSignatures() as $signature)
                            <tr>
                                <td><small>{{$signature->getR()}}</small></td>
                                <td><small>{{$signature->getS()}}</small></td>
                                <td><small>{{BaseScriptParser::SIG_HASH_TYPES[$signature->getSignatureType()]}}</small></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($inputDto->getParsedScriptSig()->getPubkeyScript()))
        @include("transaction.outputRedeem", array('redeemerDto' => $inputDto->getParsedScriptSig()->getParsedPubkeyScript()))
    @endif
    <div class="clearfix"></div>
@stop
