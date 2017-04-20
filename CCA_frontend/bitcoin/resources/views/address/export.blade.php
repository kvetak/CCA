<?php
    use \App\Model\CurrencyType;
?>


    <div class="page-header">
        <h1>Address: <small>{{$addressDto->getAddress()}}</small></h1>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table class="table">
                        <tbody>
                        <tr>
                            <th>Balance</th>
                            <td>{{$addressDto->getBalance(False, True)}} {{CurrencyType::currencyUnit($currency)}}</td>
                        </tr>
                        <tr>
                            <th>Transactions</th>
                            <td>{{count($transactions)}}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<hr/>
    <div class="row">
        @each('transaction.transactionListItemExport', $transactions, 'transaction')
    </div>

    <div class="clearfix center-"></div>
    <div class="row text-center">
        {{$pagination}}
    </div>
