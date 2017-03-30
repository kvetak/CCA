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
        $transactionModel               = CurrencyType::transactionModel($currency);
        $blockModel                     = CurrencyType::blockModel($currency);

        $transactionDto                 = $transactionModel->findByTxId($txid);
        $lastBlock                      = $blockModel->getLastBlock();
        $transactionInBlock             = $blockModel->findByHash($transactionDto->getBlockhash());
        $isTransactionConfirmed         = $transactionModel::isConfirmed($transactionInBlock->getHeight(), $lastBlock->getHeight());

        $transactionConfirmationMessage = $isTransactionConfirmed ? 'Transaction is confirmed!' : 'Transaction is not confirmed!';
        $confirmations                  = $lastBlock->getHeight() - $transactionInBlock->getHeight();
        return view('transaction/findOne',compact('transactionDto', 'displayOnlyHeader', 'transactionConfirmationMessage', 'confirmations', 'isTransactionConfirmed', 'currency'));
    }

    /**
     * Zobrazení detailu vstupu jedné transakce
     *
     * @param $currency
     * @param $txid int txid hledané transakce
     * @param $inputNo int číslo vstupu
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function inputDetail($currency, $txid, $inputNo)
    {
        $transactionModel               = CurrencyType::transactionModel($currency);
        $transaction                    = $transactionModel->findByTxId($txid);

        $inputDto   = $transaction->getInputs()[$inputNo];

        return view('transaction/inputDetail',compact('currency','inputDto','txid','inputNo'));
    }

    /**
     * Zobrazení detailu výstupu jedné transakce
     *
     * @param $currency
     * @param $txid int txid hledané transakce
     * @param $outputNo int číslo výstupu
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function outputDetail($currency, $txid, $outputNo)
    {
        $transactionModel               = CurrencyType::transactionModel($currency);
        $transaction                    = $transactionModel->findByTxId($txid);

        $outputDto  = $transaction->getOutputs()[$outputNo];

        return view('transaction/outputDetail',compact('currency','outputDto','txid','outputNo'));
    }

    /**
     * Graficka vizualizacia transakcie.
     * @param $txid
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function visualize($currency, $txid)
    {
        $transactionModel               = CurrencyType::transactionModel($currency);
        $transaction                    = $transactionModel->findByTxId($txid);
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
        $transactionModel          = CurrencyType::transactionModel($currency);
        $transactionDto                    = $transactionModel->findByTxId($txid);
        $displayOnlyHeader = false;
        return view('transaction/structure',compact('transactionDto', 'displayOnlyHeader', 'tags', 'currency'));
    }

    /**
     * Ziskanie informacie o vystupoch transakcie vo formate JSON.
     * @param $txid
     * @return string
     */
    public function outputs($currency, $txid)
    {
        $transactionModel          = CurrencyType::transactionModel($currency);
        $addressModel              = CurrencyType::addressModel($currency);

        $result = [
            'name'      => 'source',
            'children'  => [],
        ];
        $transaction = $transactionModel->findByTxId($txid);
        if(empty($transaction)){
            throw new NotFoundHttpException();
        }

        foreach($transaction->getOutputs() as $output){
            $address = $output->getSerializedAddress();
            $addressDto=$addressModel->addressExists($address);

            $element = [
                'name'          => $address,
                'value'         => $output->getValue(),
                'redeemed_tx'   => $output->isSpent() ? [$output->getSpentTxid()] : [],
            ];

            if ($addressDto != null) {
                $tags = $addressModel->getTags($addressDto);
                if (count($tags)) {
                    $element['tag'] = $tags[0]->getTag();
                    $element['url_tag'] = $tags[0]->getUrl();
                    /* $element['tag']     = Arrays::get($tags, '0.tag', null);
                     $element['url_tag'] = Arrays::get($tags, '0.url');*/
                }
            }
            $result['children'][]   = $element;
        }
        $result['value'] = $transaction->getSumOfOutputs();
        /**
         * Serializacia vysledku do formatu JSON.
         */
        return json_encode($result);
    }
}
