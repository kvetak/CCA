<?php

?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1 style="word-break:break-all;">Public key: <small>{{$uncompressesPubkey}}</small></h1>
    </div>

    <div class="row">
        <div class="col-md-14">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Summary</h3>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tbody>
                        <tr>
                            <th>Uncompressed public key</th>
                            <td style="word-break:break-all;">{{$uncompressesPubkey}}</td>
                        </tr>
                        <tr>
                            <th>Compressed public key</th>
                            <td style="word-break:break-all;">{{$pubkeyDto->getCompressedPubkey()}}</td>
                        </tr>
                        <tr>
                            <th>Ripemd160 hash of uncompressed key</th>
                            <td style="word-break:break-all;">{{$pubkeyDto->getRipe()}}</td>
                        </tr>
                        <tr>
                            <th>Ripemd160 hash of compressed key</th>
                            <td style="word-break:break-all;">{{$pubkeyDto->getCompressedRipe()}}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Known adresses</h3>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tbody>
                        @foreach($addresses as $addressDto)
                            <tr>
                                <td><a href="{{route('address_findone', ['currency' => $currency, 'address' => $addressDto->getAddress()])}}">{{$addressDto->getAddress()}}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix center-"></div>
@stop
