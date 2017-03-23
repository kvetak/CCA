<?php
    use \App\Model\CurrencyType;
?>
@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Addresses with same owner as: <small>{{$addressDto->getAddress()}}</small></h1>
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
                                    <td>{{$clusterModel->getBalance($cluster)}} {{CurrencyType::currencyUnit($currency)}}</td>
                                </tr>
                                <tr>
                                    <td>Number of addresses in cluster</td>
                                    <td>{{count($cluster->getAddresses())}}</td>
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
                    @if(count($addressTags))
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Address</th>
                                    <th>Balance</th>
                                    <th>Tag</th>
                                    <th>Url</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($addressTags as $addressTag)
                                    @foreach($addressTag->getTags() as $tag)
                                    <tr>
                                        <td><a href="{{route('address_findone',['address' => $addressTag->getAddress()->getAddress(), 'currency' => $currency])}}" target="_blank">{{$addressTag->getAddress()->getAddress()}}</a></td>
                                        <td>{{$addressTag->getAddress()->getBalance()}} {{CurrencyType::currencyUnit($currency)}}</td>
                                        <td>{{$tag->getTag()}}</td>
                                        <td><a href="{{$tag->getUrl()}}" target="_blank">{{$tag->getUrl()}}</a></td>
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
                            <tr>
                                <th>Address in cluster</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($addresses as $address)
                            <tr>
                                <th><a href="{{route('address_findone', ['address'=>$address->getAddress(), 'currency' => $currency])}}">{{$address->getAddress()}}</a></th>
                                <td>{{round($address->getBalance(), 8) + 0}} {{CurrencyType::currencyUnit($currency)}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
   {{-- <div class="row text-center">
        {{$pagination}}
    </div>--}}
@stop