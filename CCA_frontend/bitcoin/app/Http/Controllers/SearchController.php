<?php

namespace App\Http\Controllers;
use App\Model\CurrencyType;
use Illuminate\Http\Request;
use Stringy\Stringy as S;

/**
 * Radič realizujuci vyhladavanie v blockchaine.
 *
 * Class SearchController
 * @package App\Http\Controllers
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class SearchController extends Controller
{
    /**
     * Spracovaie poziadavku na vyhladavanie.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function search($currency, Request $request)
    {
        $blockModel = CurrencyType::blockModel($currency);
        $transactionModel = CurrencyType::transactionModel($currency);
        $addressModel = CurrencyType::addressModel($currency);

        /**
         * Vstupna hodnota.
         */
        $needle         = S::create($request->input('search'))->trim();
        //Dlzka vstupu
        $needleLength   = $needle->length();
        $options = [
            'options' => ['min_range' => 0 ]
        ];
        $numberInput    = filter_var((string)$needle, FILTER_VALIDATE_INT, $options) ? (int)(string)$needle : 0;
        /**
         * V pripade, ze vstup je cislo a existuje block s danou vyskou
         */

        if($numberInput && ($block=$blockModel->existByHeight($numberInput)) != null)
        {
            return redirect(
                route('block_findone', [
                    'currency' => $currency,
                    'hash' => $block->getHash()
                ])
            );
        }
        elseif($needleLength == 64) { //V pripade ze vstup ma dlzku ako Bitcoin transakcia alebo hash bloku
            //Ked existuje blok s danym hashom
            if($blockModel->existByHash($needle) != null){
                return redirect(
                    route('block_findone', [
                        'currency' => $currency,
                        'hash' => $needle
                    ])
                );
                //Ked existuje transakcia s uvedenym hashom
            }elseif($transactionModel->existsByTxId($needle) != null){
                return redirect(
                    route('transaction_findone', [
                        'currency' => $currency,
                        'txid' => $needle
                    ])
                );
            }
            //Ked sa pravdepodobne jedna o Bitcoin adresu.
        }elseif( 26 <= $needleLength && $needleLength <= 35 && $addressModel->addressExists($needle) != null){
            return redirect(
                route('address_findone', [
                    'currency' => $currency,
                    'address' => $needle
                ])
            );
        }
        /**
         * V jinom pripade se ulozi flash message Not Found. Ta je nasledne vypisana na stranke so zoznamom blokov.
         */
        \Session::flash('message',['text' => 'Not found "'.$needle.'"', 'type' => 'info']);
        return redirect('');
    }
}
