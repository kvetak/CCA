<?php

namespace App\Http\Controllers;

use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\CurrencyType;
use App\Http\Requests;
use Illuminate\Pagination\LengthAwarePaginator;
use Underscore\Types\Arrays;

/**
 * Radič pre prácu s Adresami.
 *
 * Class AddressController
 * @package App\Http\Controllers
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class AddressController extends Controller
{
    /**
     * Zobrazenie profilovej stranky Bitcoin adresy
     * @param $address
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findOne($currency, $address)
    {
        $limit = 50;
        view()->composer('transaction.transactionListItem', function($view) use($currency) {
            $view->with('currency', $currency);
        });
        $addressModelName       = CurrencyType::addressModel($currency);
        $transactionModelName   = CurrencyType::transactionModel($currency);
        $address                = new $addressModelName($address);
        $pagination         = new LengthAwarePaginator([], $address->getTransactionsCount(), $limit);
        $pagination->setPath(route('address_findone', ['address'=>$address->getAddress(), 'currency' => $currency]));
        $skip               = ($pagination->currentPage() - 1) * $limit;
        $transactions       = $transactionModelName::findByAddress($address->getAddress(), $skip, $limit)->sort(['time' => -1]);
        return view('address/findOne', compact('address','transactions', 'balance', 'pagination', 'currency'));
    }

    /**
     * Zobrazenie profilovej stranky Bitcoin penazenky.
     * @param $address
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function clusterForAddress($currency, $address)
    {
        $limit = 100;

        $modelName = CurrencyType::addressModel($currency);

        $address = new $modelName($address);
        if( ! $address->isInCluster()){
            $flashMessage = 'For address <strong>'.$address->getAddress().'</strong> does not exists other address with same owner!';
            \Session::flash('message',['type' => 'info', 'text' => $flashMessage]);
            return redirect(route('address_findone', ['address' => $address->getAddress(), 'currency' => $currency]));
        }
        $cluster = $address->getClusterModel();
        $pagination = new LengthAwarePaginator([], (int)$cluster->getSize(), $limit);
        $pagination->setPath(route('address_cluster', ['address'=>$address->getAddress(), 'currency' => $currency]));
        $skip       = ($pagination->currentPage() - 1) * $limit;
        $addresses  = $cluster->getAddresses($limit, $skip)->sort(['balance' => -1]);
        return view('address/clusterForAddress', compact('address', 'cluster', 'pagination', 'addresses', 'currency'));
    }
}
