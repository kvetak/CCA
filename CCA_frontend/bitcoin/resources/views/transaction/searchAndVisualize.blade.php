@extends('layout')
@section('content')
<div class="page-header">
    <h1>Transaction visualize</h1>
</div>
<form method="POST" action="{{route('transaction_search_visualize_submit', ['currency'=>'bitcoin'])}}">
    <div class="row">
        <div class="form-group">
            <div class="col-md-3">
                Transaction id:
            </div>
            <div class="form-inline">
                <input type="text" name="txid" class="form-control" id="txid" placeholder="Txid" size="70">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group">
            <div class="col-md-3">
                Steps forward:
            </div>
            <div class="form-inline">
                <input type="text" name="forward" class="form-control" id="forward" value="3">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group">
            <div class="col-md-3">
                Steps backward:
            </div>
            <div class="form-inline">
                <input type="text" name="backward" class="form-control" id="backward" value="3">
            </div>
        </div>
    </div>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="row">
        <div class="from-group">
            <button type="submit" class="btn btn-default">Submit</button>
        </div>
    </div>
</form>
<div class="clearfix"></div>
@stop
