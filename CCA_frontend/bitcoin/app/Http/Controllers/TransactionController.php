<?php

namespace App\Http\Controllers;

use App\Model\Bitcoin\Dto\BitcoinTransactionGraphDto;
use App\Model\Bitcoin\Dto\BitcoinUnspendOutputDto;
use App\Model\CurrencyType;
use Illuminate\Http\Request;

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
     * Zobrazí vyhledávácí formulář pro transakci, kterou chci vizualizovat
     * @param $currency
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function searchAndVisualize($currency)
    {
        return view("transaction/searchAndVisualize",compact('currency'));
    }

    /**
     * Zpracuje vyhledávací formulář pro vizualizaci transakci
     *
     * @param $currency
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function searchAndVisualizeSubmit($currency, Request $request)
    {
        $txid=$request->input("txid");
        $transactionModel               = CurrencyType::transactionModel($currency);
        $transaction                    = $transactionModel->existsByTxId($txid);

        if ($transaction == null) {
            \Session::flash('message', ['text' => 'Transaction not found ', 'type' => 'info']);
            return view("transaction/searchAndVisualize", compact('currency'));
        }

        $step_forward=$request->input("forward");
        $step_backward=$request->input("backward");

        return $this->visualize($currency,$txid,$step_forward,$step_backward);
    }

    /**
     * Graficka vizualizacia transakcie.
     * @param $currency
     * @param $txid
     * @param $steps
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function visualize($currency, $txid, $forward_steps=3, $backward_steps=3)
    {
        $transactionModel               = CurrencyType::transactionModel($currency);
        $transaction                    = $transactionModel->findByTxId($txid);

        $graph=$transactionModel->findTransactionGraph($txid,$forward_steps,$backward_steps);

        // pro všechny transakce spočítej nepoužité výstupy
        $unspend_outputs=$this->graph_unused_outputs($graph);

        return view('transaction/visualize', compact('transaction', 'currency', 'graph','unspend_outputs'));
    }

    /**
     * Získání vstupů a výstupů transakce ve formátů JSON
     * @param $currency
     * @param $txid
     * @return string
     */
    public function relations($currency, $txid)
    {
        $transactionModel           = CurrencyType::transactionModel($currency);
        $graph                      = $transactionModel->findTransactionGraph($txid,1,1);

        return $this->ajax_graph_expansion($graph);
    }

    /**
     * Vytvoří odpověd pro Ajax požadavek na rozšíření grafu
     *
     * @param BitcoinTransactionGraphDto $graph
     * @return string
     */
    private function ajax_graph_expansion(BitcoinTransactionGraphDto $graph)
    {
        $unused_output              = $this->graph_unused_outputs($graph);

        $result = [
            "transactions" => [],
            "payments"     => [],
            "unused_outputs" => []
        ];

        foreach ($graph->getTransactions() as $transaction)
        {
            $result["transactions"][]=[
                "txid" => $transaction->getTxid(),
                "coinbase" => $transaction->isCoinbase()
            ];
        }

        foreach ($graph->getPayments() as $payment)
        {
            $result["payments"][] = [
                "address" => $payment->getAddress(),
                "pays_from" => $payment->getPaysFrom(),
                "pays_to" => $payment->getPaysTo(),
                "value" => $payment->getValue()
            ];
        }

        foreach ($unused_output as $output)
        {
            $result["unused_outputs"][] = [
                "txid" => $output->getTransactionTxid(),
                "address" => $output->getAddress(),
                "value" => $output->getValue()
            ];
        }
        return json_encode($result);
    }

    /**
     * Pro graf transakcí vypočte nepoužité výstupy transakcí
     * @param BitcoinTransactionGraphDto $graph
     * @return array<BitcoinUnspendOutputDto>
     */
    private function graph_unused_outputs(BitcoinTransactionGraphDto $graph)
    {
        $unspend_outputs=array();
        foreach ($graph->getTransactions() as $transaction)
        {
            foreach ($transaction->getOutputs() as $output)
            {
                if (!$output->isSpent())
                {
                    $unspend_outputs[]=new BitcoinUnspendOutputDto(
                        $transaction->getTxid(),
                        $output->getSerializedAddress(),
                        $output->getValue());
                }
            }
        }
        return $unspend_outputs;
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
}
