<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Addresses with same owner as: <small>{{$address->getAddress()}}</small></h1>
    </div>
    <div class="row">
        <div class="col-md-5">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Summary</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <thead></thead>
                            <tbody>
                            @if(!empty($cluster))
                                <tr>
                                    <td>Total balance</td>
                                    <td>{{$cluster->getBalance()}} {{CurrencyType::currencyUnit($currency)}}</td>
                                </tr>
                                <tr>
                                    <td>Number of addresses in cluster</td>
                                    <td>{{$cluster->getSize()}}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Known identities in cluster</h3>
                </div>
                <div class="panel-body">
                    @if($cluster->getTags()->count())
                        <table class="table">
                            <thead>
                                <th>Address</th>
                                <th>Balance</th>
                                <th>Tag</th>
                                <th>Url</th>
                            </thead>
                            <tbody>
                            @foreach($cluster->getTags() as $addressTags)
                                @foreach($addressTags['tags'] as $tag)
                                    <tr>
                                        <td><a href="{{route('address_findone',['address' => $addressTags['address'], 'currency' => $currency])}}" target="_blank">{{$addressTags['address']}}</a></td>
                                        <td>{{$addressTags['balance']}} {{CurrencyType::currencyUnit($currency)}}</td>
                                        <td>{{$tag['tag']}}</td>
                                        <td><a href="{{$tag['url']}}" target="_blank">{{$tag['url']}}</a></td>
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        Identities are not known.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
            <div class="col-md-8" style="float:none; margin: 0 auto;">
                <h2 class="text-center">Addresses</h2>
                <div>
                    <table class="table table-stripped">
                        <thead>
                        <th>Address in cluster</th>
                        <th>Balance</th>
                        </thead>
                        <tbody>
                        @foreach($addresses as $address)
                            <tr>
                                <th><a href="{{route('address_findone', ['address'=>$address['address'], 'currency' => $currency])}}">{{$address['address']}}</a></th>
                                <td>{{round($address['balance'], 8) + 0}} {{CurrencyType::currencyUnit($currency)}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
    <div class="row text-center">
        {{$pagination}}
    </div>
@stop