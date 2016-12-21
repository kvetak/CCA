<?php
namespace App\Model\Bitcoin;
use Illuminate\Support\Arr;
use Underscore\Types\Arrays;

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


    /**
     * Konstanty pre identifikaciu vstupov a vystupov.
     */
    const   INPUTS_OUTPUTS_TYPE_INPUT = 1,
            INPUTS_OUTPUTS_TYPE_OUTPUT = 2;

    protected function getNodeName()
    {
        return self::NODE_NAME;
    }

    /**
     * Overenie existencie transakcie podla TxId.
     * @param $txId - txId transakcie
     * @return bool
     */
    public function existsByTxId($txId)
    {
        $c      = get_called_class();
        $model  = new $c;
        return ! empty($model->collection()->findOne(['txid' => $txId],['_id'=>true]));
    }

    /**
     * Vyhladanie transakcie podla TxId.
     * @param $txId           - txId transakcie
     * @param array $fields
     * @return array|null
     */
    public function findByTxId($txId, $fields = [])
    {
        $c      = get_called_class();
        $model  = new $c;
        return $model->collection()->findOne(['txid' => $txId], $fields);
    }

    /**
     * Vyhladanie transakcii zaradenych do specifickeho bloku.
     * @param $blockHash       - hash bloku
     * @param int $limit       - maximalny pocet vratenych zaznamov
     * @param int $skip        - pocet prvkov, ktore maju z pociatku vystupu preskocene
     * @return \MongoCursor
     */
    public function findByBlockHash($blockHash, $limit = 0, $skip = 0)
    {
        return $this->find("blockhash",$blockHash,$limit,$skip);

        /*$data   =  $model->collection()->find(["blockhash" => $blockHash],[
            'time' => true,
            'sumOfOutputs' => true,
            'txid'          => true,
        ]);
        if($limit && $skip ){
            return $data->limit($limit)->skip($skip);
        }else{
            return $data;
        }*/
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
     * Ziskanie poctu transakcii, na ktorych sa podielal uzivatel so zadanou adresou.
     * @param $address   - adresa Bitcoin uzivatela
     * @return int
     */
    public static function getTransactionsCount($address)
    {
        $c      = get_called_class();
        $model  = new $c;
        return $model->collection()->count($model->getConditionsByAddress($address));
    }

    /**
     * Zostavenie podmienok pre vyhladavanie v kolekcii podla adresy.
     * @param $address
     * @return array
     */
    public function getConditionsByAddress($address)
    {
        return [
            'inputsOutputs.addresses' => $address,
        ];
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
