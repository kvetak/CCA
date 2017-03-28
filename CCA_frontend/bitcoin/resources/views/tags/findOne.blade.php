<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Tag text: <small>{{$tagDto->getTag()}}</small></h1>
    </div>

    <div class="col-md-8">
        <table class="table table-striped">
            <caption>Tag information</caption>
            <tr>
                <td><strong>Tag</strong></td>
                <td><small>{{$tagDto->getTag()}}</small></td>
            </tr>
            <tr>
                <td><strong>Url</strong></td>
                <td><small><a href="{{$tagDto->getUrl()}}" target="_blank">{{$tagDto->getUrl()}}</a></small></td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Addresses associated with tag</h3>
        </div>
        <div class="panel-body">
            <table class="table">
                <thead>
                <tr>
                    <th>Address</th>
                    <th>Balance</th>
                </tr>
                </thead>
                <tbody>
                @foreach($addresses as $address)
                    <tr>
                        <td><a href="{{route('address_findone',['address' => $address->getAddress(), 'currency' => $currency])}}" target="_blank">{{$address->getAddress()}}</a></td>
                        <td>{{$address->getBalance()}} {{CurrencyType::currencyUnit($currency)}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop