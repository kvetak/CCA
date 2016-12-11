@extends('layout')
@section('content')
    <div class="page-header">
        <h1>Transaction search</h1>
    </div>
    <form method="GET">
        <div class="row">
            <div class="form-group">
                <div class="col-md-1">
                    From:
                </div>
                <div class="form-inline">
                    <select class="form-control">
                        <option value="0">Address</option>
                        <option value="1">Cluster of</option>
                    </select>
                    -
                    <input type="text" class="form-control" id="from-address" placeholder="Address">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <div class="col-md-1">
                    To:
                </div>
                <div class="form-inline">
                    <select class="form-control">
                        <option value="0">Address</option>
                        <option value="1">Cluster of</option>
                    </select>
                    -
                    <input type="text" class="form-control" id="to-address" placeholder="Address">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <div class="col-md-1">
                    Amount:
                </div>
                <div class="form-inline">
                    <input type="email" class="form-control" id="amount-from" placeholder="From">
                    -
                    <input type="email" class="form-control" id="amount-to" placeholder="To">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <div class="col-md-1">
                    Time:
                </div>
                <div class="form-inline">
                    <input type="email" class="form-control" id="time-from" placeholder="From">
                    -
                    <input type="email" class="form-control" id="time-to" placeholder="To">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="from-group">
                <button type="submit" class="btn btn-default">Submit</button>
                <button type="reset" class="btn btn-default">Reset</button>
            </div>
        </div>
    </form>
    <div class="clearfix"></div>
@stop