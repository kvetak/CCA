<?php

namespace App\Http\Controllers;

use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\Bitcoin\BitcoinBlockModel;
use App\Http\Requests;
use App\Model\CurrencyType;
use App\Model\InputsOutputsType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Underscore\Types\Arrays;

/**
 * Radič realizujuci pracu s transakciami.
 *
 * Class TransactionController
 * @package App\Http\Controllers
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class TransactionController extends Controller
{
    protected $transactionModelClass;

    /**
     * Zobrazenie profilu transakcie.
     * @param $txid
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findOne($currency, $txid)
    {
        $displayOnlyHeader              = False;
        $transactionModelClass          = CurrencyType::transactionModel($currency);
        $blockModelClass                = CurrencyType::blockModel($currency);
        $addressModelClass              = CurrencyType::addressModel($currency);

        $transaction                    = $transactionModelClass::findByTxId($txid);
        $lastBlock                      = (new $blockModelClass())->getLastBlock();
        $transactionInBlock             = $blockModelClass::findByHash($transaction['blockhash']);
        $isTransactionConfirmed         = $transactionModelClass::isConfirmed($transactionInBlock['height'], $lastBlock['height']);

        $transactionConfirmationMessage = $isTransactionConfirmed ? 'Transaction is confirmed!' : 'Transaction is not confirmed!';
        $confirmations                  = $lastBlock['height'] - $transactionInBlock['height'];
        $tags = $addressModelClass::getTagsByAddresses($transactionModelClass::getAddressesFromTransaction($transaction['inputsOutputs']));
        return view('transaction/findOne',compact('transaction', 'displayOnlyHeader', 'transactionConfirmationMessage', 'confirmations', 'isTransactionConfirmed', 'tags', 'currency'));
    }

    /**
     * Graficka vizualizacia transakcie.
     * @param $txid
     * @return $this
     */
    public function visualize($currency, $txid)
    {
        $transactionModelClass          = CurrencyType::transactionModel($currency);
        $transaction                    = $transactionModelClass::findByTxId($txid);
        if(empty($transaction)){
            throw new NotFoundHttpException();
        }
        return view('transaction/visualize')->with('transaction', $transaction)->with('currency', $currency);
    }

    /**
     * Ziskanie informacii o strukture transakcie.
     * @param $txid
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function structure($currency, $txid)
    {
        $transactionModelClass          = CurrencyType::transactionModel($currency);
        $addressModelClass              = CurrencyType::addressModel($currency);
        $transaction                    = $transactionModelClass::findByTxId($txid);
        $tags = $addressModelClass::getTagsByAddresses($transactionModelClass::getAddressesFromTransaction($transaction['inputsOutputs']));
        $displayOnlyHeader = false;
        return view('transaction/structure',compact('transaction', 'displayOnlyHeader', 'tags', 'currency'));
    }

    /**
     * Ziskanie informacie o vystupoch transakcie vo formate JSON.
     * @param $txid
     * @return string
     */
    public function outputs($currency, $txid)
    {
        $transactionModelClass          = CurrencyType::transactionModel($currency);
        $addressModelClass              = CurrencyType::addressModel($currency);
        $result = [
            'name'      => 'source',
            'children'  => [],
        ];
        $transaction = $transactionModelClass::findByTxId($txid, [
            'inputsOutputs.type'        => true,
            'inputsOutputs.addresses'   => true,
            'inputsOutputs.n'           => true,
            'inputsOutputs.value'       => true,
            'inputsOutputs.spentTxid'   => true,
            'inputsOutputs.spent'       => true,
        ]);
        if(empty($transaction)){
            throw new NotFoundHttpException();
        }
        $outputs        = Arrays::filterBy($transaction['inputsOutputs'], 'type', InputsOutputsType::TYPE_OUTPUT);
        $sumOfOutputs   = 0.0;
        foreach($outputs as $output){
            $address = $output['addresses'][0];
            $element = [
                'name'          => $address,
                'value'         => $output['value'],
                'redeemed_tx'   => $output['spent'] ? [$output['spentTxid']] : [],
            ];
            $addressModel       = new $addressModelClass($address);
            $tags = $addressModel->getTags();
            if(count($tags)){
                $element['tag']     = Arrays::get($tags, '0.tag', null);
                $element['url_tag'] = Arrays::get($tags, '0.url');
            }
            $result['children'][]   = $element;
            $sumOfOutputs           += (double)$output['value'];
        }
        $result['value'] = $sumOfOutputs;
        /**
         * Serializacia vysledku do formatu JSON.
         */
        return json_encode($result);
    }
}
