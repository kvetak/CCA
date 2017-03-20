<?php

namespace App\Http\Controllers;

use App\Model\CurrencyType;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $limit = 500;
        view()->composer('transaction.transactionListItem', function($view) use($currency) {
            $view->with('currency', $currency);
        });
        $addressModel       = CurrencyType::addressModel($currency);
        $transactionModel   = CurrencyType::transactionModel($currency);
        $addressDto = $addressModel->findByAddress($address);
        $transactions       = $addressModel->getTransactions($addressDto);
        $pagination         = new LengthAwarePaginator([], count($transactions), $limit);
        $pagination->setPath(route('address_findone', ['address'=>$addressDto->getAddress(), 'currency' => $currency]));
        $skip               = ($pagination->currentPage() - 1) * $limit;
        $tags               = $addressModel->getTags($addressDto);

        return view('address/findOne', compact('addressDto','transactions', 'balance', 'pagination', 'currency','tags'));
    }

    /**
     * Zobrazenie profilovej stranky Bitcoin penazenky.
     * @param $address
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function clusterForAddress($currency, $address)
    {
//        $limit = 100;
        $addressModel = CurrencyType::addressModel($currency);
        $clusterModel = CurrencyType::clusterModel($currency);

        $addressDto = $addressModel->findByAddress($address);


        if( ! $clusterModel->isInCluster($addressDto))
        {
            $flashMessage = 'For address <strong>'.$addressDto->getAddress().'</strong> does not exists other address with same owner!';
            \Session::flash('message',['type' => 'info', 'text' => $flashMessage]);
            return redirect(route('address_findone', ['address' => $addressDto->getAddress(), 'currency' => $currency]));
        }


        $cluster = $clusterModel->getClusterByAddress($addressDto);
//        $pagination = new LengthAwarePaginator([], (int)$cluster->getSize(), $limit);
//        $pagination->setPath(route('address_cluster', ['address'=>$addressDto->getAddress(), 'currency' => $currency]));
//        $skip       = ($pagination->currentPage() - 1) * $limit;
//        $addresses  = $cluster->getAddresses($limit, $skip)->sort(['balance' => -1]);
        $addresses  = $clusterModel->getAddressInCluster($cluster);
        return view('address/clusterForAddress', compact('addressDto', 'cluster', 'pagination', 'addresses', 'currency','clusterModel'));
    }
}
