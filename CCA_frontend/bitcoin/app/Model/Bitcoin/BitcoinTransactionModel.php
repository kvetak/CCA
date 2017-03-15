<?php
namespace App\Model\Bitcoin;
use App\Model\Bitcoin\Dto\BitcoinTransactionDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionOutputDto;
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
        DB_TRANS_BLOCKHAHS="blockhash",
        DB_TRANS_TIME="time",
        DB_TRANS_INPUTS="inputs",
        DB_TRANS_OUTPUTS="outputs",
        DB_TRANS_SUM_OF_INPUTS="sum_of_inputs",
        DB_TRANS_SUM_OF_OUTPUTS="sum_of_outputs",
        DB_TRANS_SUM_OF_FEES="sum_of_fees",
        DB_TRANS_UNIQUE_INPUT_ADDRESSES="unique_input_addresses",
        DB_COINBASE="coinbase";

    /**
     * Konstanty pre identifikaciu vstupov a vystupov.
     */
    const   INPUTS_OUTPUTS_TYPE_INPUT = 1,
            INPUTS_OUTPUTS_TYPE_OUTPUT = 2;

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }


    private function dto_to_array(BitcoinTransactionDto $dto)
    {
        $array=array();

        $array[self::DB_TRANS_TXID]=$dto->getTxid();
        $array[self::DB_TRANS_BLOCKHAHS]=$dto->getBlockhash();
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

    private function array_to_dto(array $array)
    {
        $dto = new BitcoinTransactionDto();

        $dto->setTxid($array[self::DB_TRANS_TXID]);
        $dto->setBlockhash($array[self::DB_TRANS_BLOCKHAHS]);
        $dto->setTime($array[self::DB_TRANS_TIME]);
        $dto->setInputs($this->input_output_decode($array[self::DB_TRANS_INPUTS]));
        $dto->setOutputs($this->input_output_decode($array[self::DB_TRANS_OUTPUTS]));
        $dto->setSumOfInputs($array[self::DB_TRANS_SUM_OF_INPUTS]);
        $dto->setSumOfOutputs($array[self::DB_TRANS_SUM_OF_OUTPUTS]);
        $dto->setSumOfFees($array[self::DB_TRANS_SUM_OF_FEES]);
        $dto->setUniqueInputAddresses($array[self::DB_TRANS_UNIQUE_INPUT_ADDRESSES]);
        $dto->setCoinbase($array[self::DB_COINBASE]);

        return $dto;
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
     * Overenie existencie transakcie podla TxId.
     * @param $txId - txId transakcie
     * @return bool
     */
    public function existsByTxId($txId)
    {
       $result = $this->findOne(self::DB_TRANS_TXID,$txId);
       return count($result) > 0;
    }

    /**
     * Vyhladanie transakcie podla TxId.
     * @param $txId           - txId transakcie
     * @return BitcoinTransactionDto
     * @throws TransactionNotFoundException
     */
    public function findByTxId($txId, $fields = [])
    {
        $data = $this->findOne(self::DB_TRANS_TXID,$txId);
        if (count($data) == 0)
        {
            throw new TransactionNotFoundException("Not found transaction with hash (txid) = ".$txId);
        }
        return $this->array_to_dto($data);
    }

    /**
     * Vyhledá několik transakcí podle jejich TXID
     *
     * @param array $txId - pole $txid, které se mají vyhledat
     * @return array <BitcoinTransactionDto>
     */
    public function findByMultipleTxid(array $txId)
    {
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
     * Vyhladanie transakcii zaradenych do specifickeho bloku.
     * @param $blockHash       - hash bloku
     * @param int $limit       - maximalny pocet vratenych zaznamov
     * @param int $skip        - pocet prvkov, ktore maju z pociatku vystupu preskocene
     * @return array<BitcoinTransactionDto>
     */
    public function findByBlockHash($blockHash, $limit = 0, $skip = 0)
    {
        $data=$this->find(self::DB_TRANS_BLOCKHAHS,$blockHash,$limit,$skip);
        $result=array();
        foreach ($data as $transaction)
        {
            $result[]=$this->array_to_dto($transaction);
        }
        return $result;
    }

    /**
     * Najdenie zoznamu transakcii na
     * @param $address
     * @param int $skip
     * @param int $limit
     * @return \MongoCursor
     */
    public static function findByAddress($address, $skip = 0, $limit = 0){
        $c      = get_called_class();
        $model  = new $c;
        $result = $model->collection()->find($model->getConditionsByAddress($address));
        if($limit){
            $result = $result->limit((int)$limit);
        }
//        if($skip){
//            $result = $result->skip((int)$skip);
//        }

        return $result;
    }

    /**
     * Metoda pre vyhladavanie transakcii medzi subjektami.
     * @todo este treba doladit ...
     * @param array $filterParams
     * @return array
     */
    public static function findTransactionsBetweenSubjects(array $filterParams)
    {
        $from   = is_array($filterParams['from']) ? $filterParams['from'] : [$filterParams['from']];
        $to     = is_array($filterParams['to']) ? $filterParams['to'] : [$filterParams['to']];
        $filter = [
            'inputsOutputs' => [
                '$all'  => [
                    [
                        '$elemMatch'    => [
                            'addresses' => ['$in'   => $from],
                            'type'      => self::INPUTS_OUTPUTS_TYPE_INPUT,
                        ],
                    ],
                    [
                        '$elemMatch'    => [
                            'addresses' => ['$in'   => $to],
                            'type'      => self::INPUTS_OUTPUTS_TYPE_OUTPUT,
                        ]
                    ]
                ]
            ]
        ];
        $postFilter = [];
//        if(Arrays::has($filterParams, 'timeFrom')){
//            $filter = Arrays::set($filterParams, 'time.$gte', (int) $filterParams['timeFrom']);
//        }
//        if(Arrays::has($filterParams, 'timeTo')){
//            $filter = Arrays::set($filterParams, 'time.$lte', (int) $filterParams['timeTo']);
//        }
//        if(Arrays::has($filterParams, 'valueFrom')){
//            $postFilter = Arrays::set($postFilter, 'value.$gte', (double) $filterParams['valueFrom']);
//        }
//        if(Arrays::has($filterParams, 'valueTo')){
//            $postFilter = Arrays::set($filterParams, 'value.$lte', (double) $filterParams['valueTo']);
//        }
        $model = new self;
        $aggregationPipeline = [
            [
                '$match' => $filter,
            ],
            [
                '$project'  => [
                    'inputsOutputs' => true,
                    'txid'          => true,
                    '_id'           => false,
                ]
            ],
            [
                '$unwind'   => '$inputsOutputs'
            ],
            [
                '$match'    => [
                    'inputsOutputs.addresses' => ['$in'   => $to],
                    'inputsOutputs.type'  => self::INPUTS_OUTPUTS_TYPE_OUTPUT,
                ]
            ],
            [
                '$group'    => [
                    '_id'   => '$txid',
                    'value' => [
                        '$sum'  => '$inputsOutputs.value'
                    ]
                ]
            ]
        ];
        if(count($postFilter)){
            $aggregationPipeline[] =[
                '$match'    => $postFilter
            ];
        }
        return $model->collection()->aggregate($aggregationPipeline);
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

    /**
     * Ziskanie zoznamu adries, ktore sa podielali na realizacii transakcie.
     * @param $inputOutputs    - pole vstupov a vystupov
     * @return array
     */
    public static function getAddressesFromTransaction($inputOutputs)
    {
        $addresses = [];
        foreach($inputOutputs as $input){
            $addresses = array_merge($addresses, $input['addresses']);
        }
        return array_unique($addresses);
    }
}
