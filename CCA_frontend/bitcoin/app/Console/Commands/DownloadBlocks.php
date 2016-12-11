<?php

namespace App\Console\Commands;
use App\Exceptions\InvalidCurrencyTypeException;
use App\Model\CurrencyType;
use App\Model\InputsOutputsType;
use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use Underscore\Types\Arrays;
use Unirest\Request;
/**
 * Realizacia stahovania obsahu blockchainu.
 *
 * Class DownloadBlocks
 * @package App\Console\Commands
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 *
 */
class DownloadBlocks extends Command
{
    /**
     * Nazov a popis konzoloveho prikazu.
     *
     * @var string
     */
    protected $signature = 'blocks:download {currency}';

    /**
     * Popis prikazu.
     *
     * @var string
     */
    protected $description = 'Block download';
    /**
     * Hash bloku, ktory sa ma stiahnut.
     * @var mixed
     */
    protected $hash;
    /**
     * Limit na maximalny pocet blokov, ktore mozu byt stiahnute na jedno spustenie.
     * @var int
     */
    protected $maxBlocksDownloadedPerRun = 100;
    /**
     * Pocitadlo stiahnutych blokov za 1 spustenie.
     * @var int
     */
    protected $downloaded = 0;
    /**
     * Objekt pracujuci s kolekciou transakcii.
     * @var
     */
    protected $transactionsCollection;
    /**
     * Objekt pracujuci s kolekciou blokov.
     * @var
     */
    protected $blocksCollection;
    /**
     * Typ kryptomeny, ktorej data sa maju importovat.
     * @var int
     */
    protected $currencyType;
    /**
     * Nazov modelu pre pracu s adresami.
     * @var mixed
     */
    protected $addressModelName;
    /**
     * Nazov modelu pre pracu s transakciami.
     * @var mixed
     */
    protected $transactionModelName;
    /**
     * Nazov modelu pre pracu s blokmi.
     * @var mixed
     */
    protected $blockModelName;
    /**
     * Nazov modelu pre pracu so zhlukmi.
     * @var mixed
     */
    protected $clusterModelName;
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "start: ".(string)Carbon::now()."\n";
        $this->currencyType             = CurrencyType::BITCOIN;
        $this->blockModelName           = CurrencyType::blockModel($this->currencyType);
        $this->transactionModelName     = CurrencyType::transactionModel($this->currencyType);
        $this->addressModelName         = CurrencyType::addressModel($this->currencyType);
        $this->clusterModelName         = CurrencyType::clusterModel($this->currencyType);
        $this->blocksCollection         = (new $this->blockModelName())->collection();
        $this->transactionsCollection   = (new $this->transactionModelName())->collection();
        $this->hash                     = $this->getNextBlockHash();

        /**
         * V pripade, ze nie je aky blok stahovat tak sa uloha ukonci.
         */
        if(empty($this->hash)){
            return;
        }
        do{
            /**
             * Nacitanie informacii o pozadovanom bloku pomocou JSON-RPC
             */
            $blockResponse = $this->getItem('getblock', [$this->hash]);
            $blockData     = $blockResponse->result;
            /**
             * V pripade, ze nastala chyba tak se ukonci vykonavani.
             */
            if( ! empty($blockResponse->error)){
                break;
            }
            /**
             * Spracovanie informacii o transakciach v bloku a nasledne nastavenie sumarnych charakteristik pre blok.
             */
            $transactionsProcessingResult   = $this->processTransactions($blockData->tx);
            $blockData->sum_of_inputs       = $transactionsProcessingResult['sumOfInputs'];
            $blockData->sum_of_outputs      = $transactionsProcessingResult['sumOfOutputs'];
            $blockData->sum_of_fees         = $transactionsProcessingResult['sumOfFees'];
            $blockData->transactions        = $transactionsProcessingResult['count'];
            /**
             * Odstranenie poloziek, ktore sa nebudu ukladat do databaze.
             */
            unset($blockData->tx);
            unset($blockData->confirmations);
            /**
             * Ulozenie informacii o bloku do kolekcie blokov.
             */
            $this->blocksCollection->insert($blockData);
            /**
             * Nastavenie priznaku, ktory rozhoduje o tom ci uz vznikol dalsi blok.
             */
            $next       = isset($blockData->nextblockhash);
            /**
             * Nastavenie hashu dalsieho bloku.
             */
            $this->hash  = $next ? $blockData->nextblockhash : null;
            /**
             * Zvysenie pocitadla stiahnutych blokov.
             */
            $this->increaseDownloadedCounter();

        }while( $next && ! $this->isDownloadLimitReached()); //V pripade ze existuje dalsi blok a zaroven neni dosiahnuty limit na stiahnutia.
        echo "End: ".(string)Carbon::now()."<br/>";
    }

    /**
     * Spracovanie transkacii obsiahnutych v bloku.
     * @param $transactions
     * @return array
     */
    protected function processTransactions($transactions)
    {
        $cModelClass = $this->clusterModelName;
        $tModelClass = $this->transactionModelName;
        /**
         * Pociatocne hodnoty pre sumarne charakteristiky pre transakcie v bloku.
         */
        $totalSumOfInputs   = 0.0;
        $totalSumOfOutputs  = 0.0;
        $i                  = 0;
        /**
         * Spracovanie jednotlivych transakcii obsiahnutych v bloku.
         */
        foreach($transactions as $tx){

            $sumOfInputs    = 0.0;
            $sumOfOutputs   = 0.0;
            $inputs         = [];
            $inputsOutputs  = [];
            /**
             * Stiahnutie informacii o transakcii.
             */
            $transactionResponse = $this->getItem("getrawtransaction",[$tx, 1]);
            dump($transactionResponse);
            continue;
            /**
             * V pripade, ze operacia skoncila chybov tak sa vykonavanie ukonci.
             */
            if(! empty($transactionResponse->error)){
                continue;
            }
            $transactionData = $transactionResponse->result;
            /**
             * Ked uz existuje transakcia s uvedenym hashom tak sa preskoci.
             */
            if($tModelClass::existsByTxId($transactionData->txid)){
                echo "Transakcia: {$transactionData->txid} existuje!";
                continue;
            }
            /**
             * Spracovanie informacii o vystupoch transakcie.
             */
            foreach($transactionData->vout as $vout){
                $element = [
                    'type'          => InputsOutputsType::TYPE_OUTPUT,
                    'value'         => (double)$vout->value,
                    'n'             => (int)$vout->n,
                    'spent'         => False,
                ];
                $sumOfOutputs   += (double)$vout->value;
                if(isset($vout->scriptPubKey->addresses)){
                    $element['addresses'] = $vout->scriptPubKey->addresses;
                    foreach($element['addresses'] as $addr){
                        (new $this->addressModelName($addr))->increaseBalanace($element['value']);
                    }
                }
                $inputsOutputs[] = $element;
            }

            /**
             * Spracovanie informacii o vstupoch transakcie.
             */
            foreach($transactionData->vin as $vin){
                $element = [
                    'type'      => InputsOutputsType::TYPE_INPUT,
                    'sequence'  => $vin->sequence,
                ];
                if(isset($vin->coinbase)){
                    $element['coinbase']    = $vin->coinbase;
                    $element['value']       = (double)$transactionData->vout[0]->value;
                    $element['addresses']   = [];
                }else{
                    $inputTransaction   = $this->transactionsCollection->findOne(["txid" => $vin->txid],['inputsOutputs' => true]);
                    $t                   = $this->findOutput($inputTransaction['inputsOutputs'], (int)$vin->vout);
                    if( ! $t['spent']){
                        $this->transactionsCollection->update(
                            [
                                '_id' => new \MongoId($inputTransaction['_id']),
                                'inputsOutputs'     => [
                                    '$elemMatch'    => [
                                        'type'      => InputsOutputsType::TYPE_OUTPUT,
                                        'n'         => (int)$vin->vout,
                                    ],
                                ],
                            ],
                            [
                                '$set' => [
                                    'inputsOutputs.$.spent'     => true,
                                    'inputsOutputs.$.spentTxid' => $transactionData->txid,
                                    'inputsOutputs.$.spentTs'   => $transactionData->time,
                                ]
                            ]);
                    }
                    $element['txid']        = $vin->txid;
                    $element['vout']        = $vin->vout;
                    $element['value']       = (double)$t['value'];
                    $element['addresses']   = isset($t['addresses']) ? $t['addresses'] : [];
                    foreach($element['addresses'] as $inputAddress){
                        if( ! Arrays::contains($inputs, $inputAddress)){
                            $inputs[] = $inputAddress;
                        }
                        if( ! $t['spent']){
                            (new $this->addressModelName($inputAddress))->decreaseBalance($element['value']);
                        }
                    }
                }
                $inputsOutputs[]        = $element;
                $sumOfInputs        += (double)$element['value'];
            }

            /**
             * Data popisujuce transakciu, ktore budu ulozene do databaze.
             */
            $transaction     = [
                'txid'          => $transactionData->txid,
                'version'       => $transactionData->version,
                'locktime'      => $transactionData->locktime,
                'blockhash'     => $transactionData->blockhash,
                'time'          => $transactionData->time,
                'blocktime'     => $transactionData->blocktime,
                'inputsOutputs' => $inputsOutputs,
            ];
            /**
             * Doplnenie sumarnych charakteristik ku transakcii.
             */
            $transaction['sumOfInputs']     = $sumOfInputs;
            $transaction['sumOfOutputs']    = $sumOfOutputs;
            $transaction['sumOfFees']       = abs($sumOfInputs - $sumOfOutputs);
            $transaction['uniqueInputs']    = count($inputs);
            /**
             * Ulozenie transakcie do kolekcie transakcii.
             */
            $this->transactionsCollection->insert($transaction);
            /**
             * V pripade, ze transakcia ma na vstupoch viac ako 1 adresu tak sa spusti proces clusterizacie.
             */
            if($transaction['uniqueInputs'] > 1){
                $cModelClass::createCluster($inputs);
            }

            /**
             * Do sumarnej charakteristiky bloku (mnozstvo vstupov/vystupov) su pricitane informacie o vstupoch/vystupoch spracovavanej transakcie.
             */
            $totalSumOfInputs += $sumOfInputs;
            $totalSumOfOutputs  += $sumOfOutputs;
            ++$i;
        }
        /**
         * Vratenie vysledku, ktory obsahuje sumarne charakteristiky o importovanych transakciach.
         */
        return [
            'count'         => $i,                                 //pocet transakcii v bloku
            'sumOfInputs'   => $totalSumOfInputs,                  //celkove mnozstvo prostriedkov na vstupoch vsetkych transakciach
            'sumOfOutputs'  => $totalSumOfOutputs,                 //celkove mnozstvo prostriedkov na vystupoch vsetkych transakcii
            'sumOfFees'     => abs($totalSumOfOutputs - $totalSumOfInputs), //celkova vyska poplatkov za realizaciu transakcii
        ];
    }

    /**
     * Ziskanie hashu nasledujuceho bloku, ktory sa ma stiahnut.
     * @return mixed
     */
    protected function getNextBlockHash()
    {
        $b          = new $this->blockModelName();
        /**
         * Najdenie posledneho importovaneho bloku.
         */
        $lastBlock  = $b->getLastBlock();
        /**
         * V pripade ze nie je u posledneho bloku nastaveny hash dalsieho neimportovaneho bloku tak sa pouzije
         * JSON RPC metoda getinfo, pomocou ktorej je zistena informacia o aktualnom pocte importovanych blokov.
         * Nasledne na zaklade jej odpovede sa rozhodne ci sa pouzije metoda getblockhash pre ziskanie hashu dalsieho neimportovaneho bloku
         * alebo sa nebude importovat vobec pretoze nevznikol ziaden novy blok.
         */
        if( empty($lastBlock['nextblockhash']) ){
            $getInfoResponseData = $this->getItem('getinfo', [])->result;
            if($lastBlock['height'] != $getInfoResponseData->blocks){
                $getBlockHashResponse   = $this->getItem('getblockhash', [$lastBlock['height'] + 1]);
                $nextHash               = $getBlockHashResponse->result;
                /**
                 * Ulozenie hashu nasledujuceho bloku do posledneho importovaneho bloku.
                 */
                $b->collection()->findAndModify(['hash' => $lastBlock['hash']],
                [
                    '$set'  => [
                        'nextblockhash' => $nextHash,
                    ]
                ]);
                /**
                 * Vratenie hashu bloku, ktory sa bude importovat.
                 */
                return $nextHash;
            }
        }else{
            /**
             * Vratenie hashu nasledujuceho bloku, ktory sa bude importovat.
             */
            return $lastBlock['nextblockhash'];
        }
    }

    /**
     * @param $inputOutputs
     * @param $index
     * @return null
     */
    protected function findOutput($inputOutputs, $index){
        foreach($inputOutputs as $inputOutput)
        {
            if($inputOutput['type'] == InputsOutputsType::TYPE_OUTPUT && $inputOutput['n'] == $index){
                return $inputOutput;
            }
        }
        return null;
    }

    /**
     * Komunikacia s Bitcoin demonom pomocou JSON-RPC API.
     * @param $method     - metoda API, ktora ma byt zavolana
     * @param $params     - parametre metody
     * @return mixed
     */
    protected function getItem($method, $params)
    {
        $data = [
            "jsonrpc"   => "2.0",
            "method"    => $method,
            "params"    => $params,
            "id"        => 1,
        ];
        list($url, $username, $password) = $this->getRPCConnectionParameters();
        $response   = Request::post($url, [], json_encode($data), $username, $password);
        if ($response->code != 200)
        {
            throw new \Exception("Downloading from bitcoind failed with error code ". $response->code . PHP_EOL. print_r($response,true));
        }
        return $response->body;
    }

    /**
     * Kontrola ci bol dosiahnuty limit pre maximalny pocet stiahnutych blokov na jedno spustenie.
     * @return bool
     */
    protected function isDownloadLimitReached()
    {
        if( $this->maxBlocksDownloadedPerRun < 0 ){
            return False;
        }
        return  ! ($this->downloaded < $this->maxBlocksDownloadedPerRun);
    }

    /**
     * Navysenie pocitadla stiahnutych blokov.
     */
    protected function increaseDownloadedCounter()
    {
        $this->downloaded += 1;
        echo $this->downloaded.PHP_EOL;
    }

    /**
     * Ziskanie potrebnych parametrov na napojenie na RPC server.
     *
     * @return array
     * @throws InvalidCurrencyTypeException
     */
    protected function getRPCConnectionParameters()
    {
        switch($this->currencyType){
            case CurrencyType::BITCOIN:
                return [
                    getenv('BITCOINDRPC_URL'),
                    getenv('BITCOINDRPC_USERNAME'),
                    getenv('BITCOINDRPC_PASSWORD')
                ];
            case CurrencyType::LITECOIN:
                return [
                    getenv('LITECOINDRPC_URL'),
                    getenv('LITECOINDRPC_USERNAME'),
                    getenv('LITECOINDRPC_PASSWORD')
                ];
            default:
                throw new InvalidCurrencyTypeException();
        }
    }
}
