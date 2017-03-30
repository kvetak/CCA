<?php
    use App\Model\Bitcoin\ScriptParser\Dto\AbstractBitcoinScriptDto;
?>
<div class="clearfix"></div>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Payment information</h3>
            </div>
            <div class="panel-body">
                <table class="table">
                    <tr>
                        <td><strong>Payment type</strong></td>
                        <td> {{AbstractBitcoinScriptDto::PAY_METHODS[$redeemerDto->getType()]}}</td>
                    </tr>
                    <tr>
                        <td><strong>Text data in output</strong></td>
                        <td>{{$redeemerDto->getData()}}</td>
                    </tr>
                    @if($redeemerDto->getType() == AbstractBitcoinScriptDto::PAY_TO_SCRIPT_HASH)
                        <tr>
                            <td><strong>Script hash</strong></td>
                            <td>{{$redeemerDto->getHash()}}</td>
                        </tr>
                    @elseif($redeemerDto->getType() == AbstractBitcoinScriptDto::PAY_TO_MULTISIG)
                        <tr>
                            <td><strong>Number of key</strong></td>
                            <td>{{$redeemerDto->getMultisigKeyCount()}}</td>
                        </tr>
                        <tr>
                            <td><strong>Signatures required</strong></td>
                            <td>{{$redeemerDto->getMultisigRequiredKeys()}}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Addresses that can to redeem this output</h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    @foreach ($redeemerDto->getAddresses() as $address)
                        <tr>
                            <td><small><a href="{{route('address_findone', ['address' => $address, 'currency' => $currency])}}">{{$address}}</a></small></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Public keys used in output</h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped">
                    @foreach ($redeemerDto->getPubkeys() as $pubkey)
                        <tr>
                            <td><small>{{$pubkey}}</small></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>