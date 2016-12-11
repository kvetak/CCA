<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use Stringy\Stringy as S;
use App\Model\Bitcoin\BitcoinBlockModel;
use App\Model\Bitcoin\BitcoinTransactionModel;
use Underscore\Types\Arrays;

/**
 *
 * Radič realizujuci vyhladavanie v blockchaine.
 *
 * Class SearchController
 * @package App\Http\Controllers
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class SearchController extends Controller{
    /**
     * Spracovaie poziadavku na vyhladavanie.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function search(Request $request)
    {
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
        if($numberInput && BitcoinBlockModel::exists(['height' => $numberInput])){
            $block = BitcoinBlockModel::findByHeight($numberInput, ['hash' => true, "_id" => false]);
            return redirect(
                route('block_findone', [
                    'hash' => $block['hash']
                ])
            );
        }
        elseif($needleLength == 64) { //V pripade ze vstup ma dlzku ako Bitcoin transakcia alebo hash bloku
            //Ked existuje blok s danym hashom
            if(BitcoinBlockModel::exists(["hash" => (string)$needle])){
                return redirect(
                    route('block_findone', [
                        'hash' => $needle
                    ])
                );
                //Ked existuje transakcia s uvedenym hashom
            }elseif(BitcoinTransactionModel::existsByTxId((string)$needle)){
                return redirect(
                    route('transaction_findone', [
                        'txid' => $needle
                    ])
                );
            }
            //Ked sa pravdepodobne jedna o Bitcoin adresu.
        }elseif( 26 <= $needleLength && $needleLength <= 35 && Arrays::contains([1, 3, 4], (int)(string)$needle->at(0))){
            return redirect(
                route('address_findone', [
                    'address' => (string)$needle
                ])
            );
        }
        /**
         * V jinom pripade se ulozi flash message Not Found. Ta je nasledne vypisana na stranke so zoznamom blokov.
         */
        \Session::flash('message',['text' => 'Not found!', 'type' => 'info']);
        return redirect(route('homepage'));
    }
}
