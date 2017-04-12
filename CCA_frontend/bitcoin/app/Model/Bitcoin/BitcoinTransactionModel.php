<?php
namespace App\Model\Bitcoin;
use App\Model\Bitcoin\Dto\BitcoinTransactionDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionGraphDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionOutputDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionPaymentDto;
use App\Model\Exceptions\TransactionNotFoundException;

/**
 * Praca s transakciami.
 *
 * Class BitcoinTransactionModel
 * @package App\Model\Bitcoin
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class BitcoinTransactionModel extends BaseBitcoinModel
{
    /**
     * Název node, jak je uložen v databázi
     */
    const NODE_NAME="transaction";

    const DB_TRANS_TXID="txid",
        DB_TRANS_BLOCKHASH="blockhash",
        DB_TRANS_TIME="time",
        DB_TRANS_INPUTS="inputs",
        DB_TRANS_OUTPUTS="outputs",
        DB_TRANS_SUM_OF_INPUTS="sum_of_inputs",
        DB_TRANS_SUM_OF_OUTPUTS="sum_of_outputs",
        DB_TRANS_SUM_OF_FEES="sum_of_fees",
        DB_TRANS_UNIQUE_INPUT_ADDRESSES="unique_input_addresses",
        DB_COINBASE="coinbase";

    const DB_REL_PAYS_TO="pays_to",
        DB_REL_PROP_FROM="from",
        DB_REL_PROP_TO="to",
        DB_REL_PROP_ADDRESS="addr",
        DB_REL_PROP_VALUE="value";

    private static $instance;
    /**
     * Tovární metoda, vrací instanci třídy
     *
     * @return BitcoinTransactionModel volané třídy
     */
    public static function getInstance()
    {
        if (self::$instance == null){
            self::$instance= new self();
        }
        return self::$instance;
    }

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }


    private function dto_to_array(BitcoinTransactionDto $dto)
    {
        $array=array();

        $array[self::DB_TRANS_TXID]=$dto->getTxid();
        $array[self::DB_TRANS_BLOCKHASH]=$dto->getBlockhash();
        $array[self::DB_TRANS_TIME]=$dto->getTime();
        $array[self::DB_TRANS_INPUTS]=$this->input_output_encode($dto->getInputs());
        $array[self::DB_TRANS_OUTPUTS]=$this->input_output_encode($dto->getOutputs());
        $array[self::DB_TRANS_SUM_OF_INPUTS]=$dto->getSumOfInputs();
        $array[self::DB_TRANS_SUM_OF_OUTPUTS]=$dto->getSumOfOutputs();
        $array[self::DB_TRANS_SUM_OF_FEES]=$dto->getSumOfFees();
        $array[self::DB_TRANS_UNIQUE_INPUT_ADDRESSES]=$dto->getUniqueInputAddresses();
        $array[self::DB_COINBASE]=$dto->isCoinbase();

        return $array;
    }

    public static function array_to_dto(array $array)
    {
        $dto = new BitcoinTransactionDto();

        $dto->setTxid($array[self::DB_TRANS_TXID]);
        $dto->setBlockhash($array[self::DB_TRANS_BLOCKHASH]);
        $dto->setTime($array[self::DB_TRANS_TIME]);
        $dto->setInputs(self::input_output_decode($array[self::DB_TRANS_INPUTS]));
        $dto->setOutputs(self::input_output_decode($array[self::DB_TRANS_OUTPUTS]));
        $dto->setSumOfInputs($array[self::DB_TRANS_SUM_OF_INPUTS]);
        $dto->setSumOfOutputs($array[self::DB_TRANS_SUM_OF_OUTPUTS]);
        $dto->setSumOfFees($array[self::DB_TRANS_SUM_OF_FEES]);
        $dto->setUniqueInputAddresses($array[self::DB_TRANS_UNIQUE_INPUT_ADDRESSES]);
        $dto->setCoinbase($array[self::DB_COINBASE]);

        return $dto;
    }

    /**
     * Vytvoří v databázi indexy pro hledání transakcí
     */
    public function createIndexes()
    {
        $this->createIndex(self::DB_TRANS_TXID);
    }

    /**
     * Uloží uzel do databáze
     *
     * @param BitcoinTransactionDto $dto
     */
    public function storeNode(BitcoinTransactionDto $dto)
    {
        $values=$this->dto_to_array($dto);
        $this->insert($values);
    }

    /**
     * Smaže všechny bloky z databáze
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }

    /**
     * Vrací uzly z databáze, seřazené od nejnovějšího
     * @param int $limit - Maximální počet uzlů, který bude vrácen
     * @param int $skip - počet uzlů který bude přeskočen od začátku výstupu
     * @return array <ID ; array <property_name ; property_value>>;
     */
    public function findAll($limit = 0, $skip = 0)
    {
        $data=$this->findAllNodes($limit,$skip);
        $blocks=array();

        foreach ($data as $block_data)
        {
            $blocks[]=$this->array_to_dto($block_data);
        }

        return $blocks;
    }


    /**
     * Smaže všechny transakce, které patří do daného bloku
     * @param hash String - hash bloku
     */
    public function deleteByHash($hash)
    {
        $this->delete(self::DB_TRANS_BLOCKHASH,$hash);
    }

    /**
     * Overenie existencie transakcie podla TxId.
     * @param $txId - txId transakcie
     * @return BitcoinTransactionDto|null
     */
    public function existsByTxId($txId)
    {
        $data = $this->findOne(self::DB_TRANS_TXID,$txId);
        if (count($data) == 0)
        {
            return null;
        }
        return $this->array_to_dto($data);
    }

    /**
     * Vyhladanie transakcie podla TxId.
     * @param $txId           - txId transakcie
     * @return BitcoinTransactionDto
     * @throws TransactionNotFoundException
     */
    public function findByTxId($txId)
    {
        $transaction = $this->existsByTxId($txId);
        if ($transaction == null)
        {
            throw new TransactionNotFoundException("Not found transaction with hash (txid) = ".$txId);
        }
        return $transaction;
    }

    /**
     * Vyhledá několik transakcí podle jejich TXID
     *
     * @param array $txId - pole $txid, které se mají vyhledat
     * @return array <BitcoinTransactionDto>
     */
    public function findByMultipleTxid(array $txId)
    {
        if (count($txId) == 0)
        {
            return array();
        }
        $array=$this->findByArray(self::DB_TRANS_TXID,$txId,self::DB_TRANS_TIME);

        $result=array();
        foreach($array as $node_array)
        {
            $result[]=$this->array_to_dto($node_array);
        }
        return $result;
    }

    /**
     * Najde výstup transakce podle jejího ID a čísla výstupu
     * @param $txid - ID transakce (hash)
     * @param $n - číslo výstupní transakce
     * @return BitcoinTransactionOutputDto
     */
    public function findTransactionOutput($txid, $n)
    {
        $transaction=$this->findByTxId($txid);
        return $transaction->getOutputs()[$n];
    }

    /**
     * Aktualizace výstupu transakce
     * @param $txid
     * @param $n
     * @param BitcoinTransactionOutputDto $outputDto
     */
    public function updateTransactionOutput($txid,$n, BitcoinTransactionOutputDto $outputDto)
    {
        $transaction=$this->findByTxId($txid);
        $outputs=$transaction->getOutputs();
        $outputs[$n]=$outputDto;
        $this->update(self::DB_TRANS_TXID,$txid,
            array(
                self::DB_TRANS_OUTPUTS => $this->input_output_encode($outputs)
            )
        );
    }

    /**
     * Najde graf transakcí, začínající z dané trasnakce a rozpínající se na oba směry
     *
     * @param string $txid identifikátor počáteční transakce
     * @param int $forward_steps počet kroků, které se mají rozvinout do budoucnosti od zadané transakce
     * @param int $backward_steps počet kroků, které se mají rozvinou do minulosti od zadané transakce
     * @return BitcoinTransactionGraphDto graf transakcí
     */
    public function findTransactionGraph($txid, $forward_steps=2, $backward_steps=2)
    {
        $data=$this->findRelatedNodesAndRelations(
            array(self::DB_TRANS_TXID => $txid),
            self::DB_REL_PAYS_TO,
            $forward_steps,
            $backward_steps
        );

        $transactions=array();
        foreach ($data[self::RETURN_NODES] as $node)
        {
            $transactions[]=$this->array_to_dto($node);
        }

        $payments=array();
        foreach ($data[self::RETURN_RELATIONS] as $payment)
        {
            $paymentDto=new BitcoinTransactionPaymentDto();
            $paymentDto->setValue($payment[self::DB_REL_PROP_VALUE]);
            $paymentDto->setPaysFrom($payment[self::DB_REL_PROP_FROM]);
            $paymentDto->setPaysTo($payment[self::DB_REL_PROP_TO]);
            $paymentDto->setAddress($payment[self::DB_REL_PROP_ADDRESS]);

            $payments[]=$paymentDto;
        }

        $graph=new BitcoinTransactionGraphDto();
        $graph->setPayments($payments);
        $graph->setTransactions($transactions);
        return $graph;
    }

    /**
     * Vytvoří relaci mezi dvěma transakcemi a uloží hodnotu platby
     * @param BitcoinTransactionPaymentDto $dto
     */
    public function addPaymentRelation(BitcoinTransactionPaymentDto $dto)
    {
        $this->makeRelation(
            array(self::DB_TRANS_TXID => $dto->getPaysFrom()),
            array(
                self::RELATION_TYPE => self::DB_REL_PAYS_TO,
                self::RELATION_PROPERTIES => array(
                    self::DB_REL_PROP_ADDRESS => $dto->getAddress(),
                    self::DB_REL_PROP_FROM => $dto->getPaysFrom(),
                    self::DB_REL_PROP_TO => $dto->getPaysTo(),
                    self::DB_REL_PROP_VALUE => $dto->getValue()
                )
            ),
            self::NODE_NAME,
            array(self::DB_TRANS_TXID => $dto->getPaysTo())
        );
    }

    /**
     * Vyhladanie transakcii zaradenych do specifickeho bloku.
     * @param $blockHash       - hash bloku
     * @param int $limit       - maximalny pocet vratenych zaznamov
     * @param int $skip        - pocet prvkov, ktore maju z pociatku vystupu preskocene
     * @return array<BitcoinTransactionDto>
     */
    public function findByBlockHash($blockHash)
    {
        $data=$this->findRelatedNodes(
            array(BitcoinBlockModel::DB_HASH => $blockHash),
            BitcoinBlockModel::DB_REL_CONTAINS_TRANSACTION,
            BitcoinBlockModel::NODE_NAME);

        $result=array();
        foreach ($data as $transaction)
        {
            $result[]=$this->array_to_dto($transaction);
        }
        return $result;
    }

    /**
     * Overenie stavu potvrdenia transakcii.
     * @param $inBlock       - vyska bloku, v ktorom je transakcia zahrnuta
     * @param $currentBlock  - vyska naposledy importovaneho bloku blockchainu.
     * @return bool
     */
    public static function isConfirmed($inBlock, $currentBlock)
    {
        return ($currentBlock - $inBlock) >= 6;
    }
}
