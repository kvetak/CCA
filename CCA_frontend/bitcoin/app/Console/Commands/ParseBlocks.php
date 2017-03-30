<?php
/**
 * @file: ImportBlocks.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Console\Commands;

use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\Bitcoin\BitcoinBlockModel;
use App\Model\Bitcoin\BitcoinClusterModel;
use App\Model\Bitcoin\BitcoinOutOfOrderBlockModel;
use App\Model\Bitcoin\BitcoinTagModel;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\Bitcoin\BitcoinUtils;
use App\Model\Bitcoin\Dto\BitcoinBlockDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionInputDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionOutputDto;
use App\Model\Bitcoin\ScriptParser\ScriptPubkeyParser;
use App\Model\Bitcoin\ScriptParser\ScriptSigParser;
use App\Model\Blockchain\Parser\BlockchainParser;
use App\Model\Blockchain\Parser\BlockDto;
use App\Model\Blockchain\Parser\PositionDto;
use App\Model\Blockchain\Parser\TransactionDto;
use App\Model\Blockchain\PositionManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class ParseBlocks
 * @package App\Console\Commands
 *
 * Třída pro parsování blockchainu
 * Načte obsah blockchainu ze souborů z disku a uloží jej do databáze
 */
class ParseBlocks extends Command
{
    const COINBASE_N=4294967295;
    const GENESIS_BLOCK_PREVIOUS_HASH="0000000000000000000000000000000000000000000000000000000000000000";

    /**
     * Název a popis konzolového příkazu
     * @var string
     */
    protected $signature = 'blocks:parse 
        {path? : Path to directory, where blockchain is located}
        {--clear : Delete blockchain in database and clear last parsing location}
        {--totalClear : Delete everything in database}
        {--initDb : Prepare database for this aplication}
        {--first_file=blk00000.dat : Name of first file of blockchain (take effect only when last parsing location is empty)}
        {--number_of_blocks=100 : Number of blocks, that should be parsed in one run}';

    /**
     * Popis prikazu
     * @var string
     */
    protected $description = 'Parse bitcoin blocks from files on disk.'.PHP_EOL.
        "After one run of this command is done, the possition where parsing stopped will be stored in database,".
        "so next time will start from this possition";


    /**
     * Počet bloků, které se mají parsovat za jeden běh tohoto skriptu
     * @var int
     */
    private $number_of_blocks;

    /**
     * Název prvního souboru v blockchainu
     * @var string
     */
    private $name_of_first_file;


    /**
     * @var PositionManager
     */
    private $positionManager;


    /**
     * @var BitcoinBlockModel
     */
    private $bitcoinBlockModel;

    /**
     * @var BitcoinTransactionModel
     */
    private $bitcoinTransactionModel;

    /**
     * @var BitcoinAddressModel
     */
    private $bitcoinAddressModel;

    /**
     * @var BitcoinClusterModel
     */
    private $bitcoinClusterModel;

    /**
     * @var BitcoinOutOfOrderBlockModel
     */
    private $bitcoinOutOfOrderBlockModel;

    /**
     * @var BitcoinTagModel;
     */
    private $bitcoinTagModel;

    /**
     * @var ScriptPubkeyParser
     */
    private $scriptPubkeyParser;

    /**
     * @var ScriptSigParser
     */
    private $scriptSigParser;


    /**
     * Hash bloku který je právě zpracováván
     * Používá se v případě chyby při zpracování bloku, aby byla informace o tom který blok a jeho související věci se mají smazat
     * @var string blockhash
     */
    private $processing_block;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("start: ".(string)Carbon::now());
        $this->positionManager=new PositionManager();
        $this->bitcoinBlockModel=BitcoinBlockModel::getInstance();
        $this->bitcoinTransactionModel=BitcoinTransactionModel::getInstance();
        $this->bitcoinAddressModel=BitcoinAddressModel::getInstance();
        $this->bitcoinOutOfOrderBlockModel= BitcoinOutOfOrderBlockModel::getInstance();
        $this->bitcoinClusterModel= BitcoinClusterModel::getInstance();
        $this->bitcoinTagModel = BitcoinTagModel::getInstance();
        $this->scriptPubkeyParser=new ScriptPubkeyParser();
        $this->scriptSigParser=new ScriptSigParser($this->scriptPubkeyParser);
        $this->processing_block=null;

        if ($this->option("clear"))
        {
            $this->clearDatabase();
            return;
        }

        if ($this->option("totalClear"))
        {
            $this->totalClearDatabase();
            return;
        }

        if ($this->option("initDb"))
        {
            $this->initDatabase();
            return;
        }

        $path=$this->argument("path");
        if (empty($path))
        {
            $this->error("Required path to blockchain");
            return;
        }

        $this->number_of_blocks=$this->option("number_of_blocks");
        if (!is_numeric($this->number_of_blocks) || $this->number_of_blocks <= 0)
        {
            $this->error("Number of blocks must be non negative number");
            return;
        }

        $this->name_of_first_file=$this->option("first_file");
        $this->parse($path);

        $this->info("End: ".(string)Carbon::now());
    }


    /**
     * Parsování bloků z blockchainu
     *
     * @param $path String  Cesta k souborů blockchainu
     */
    private function parse($path)
    {
        $parser=new BlockchainParser($path);
        $position_dto=$this->positionManager->load();
        if ($position_dto != null)
        {
            $parser->startFrom($position_dto);
        }
        else{
            $parser->startFrom(new PositionDto($this->name_of_first_file,0));
        }

        // TODO vymyslet dávkování, ať se nenačítá ze vstupu 1 hodnota, ale něco většího
        for ($i = 0 ; $i < $this->number_of_blocks ; $i++) // TODO: nastavit na hodnotu zadanou na vstupu
        {
            // načtení bloků ze souborů blockchainu
            $blocks= $parser->parse(1);

            // zpracování bloku a uložení do db
            $processed_blocks=array();
            try {
                foreach ($blocks as $block) {
                    $processed_blocks[] = $this->process_block($block);
                }
            }
            catch (\Exception $e)
            {
                // pokud nastala chyba, proveď rollback - smazat všechny uzly, které byly vloženy ale nejsou zaevidovaný v position manageru
                $this->bitcoinBlockModel->deleteByHash($this->processing_block);
                $this->bitcoinTransactionModel->deleteByHash($this->processing_block);
                $this->bitcoinOutOfOrderBlockModel->deleteByBlockhash($this->processing_block);

                foreach ($processed_blocks as $blockhash)
                {
                    $this->bitcoinTransactionModel->deleteByHash($blockhash);
                    $this->bitcoinBlockModel->deleteByHash($blockhash);
                    $this->bitcoinOutOfOrderBlockModel->deleteByBlockhash($blockhash);
                }
                throw $e;
            }
            $this->positionManager->store($parser->getPosition());
        }
    }

    /**
     * Zpracuj celý načtený blok
     * Vytáhne z bloku veškeré užitečné informace a uloží je do blockchainu
     *
     * @param BlockDto $blockDto Načtený blok
     */
    private function process_block(BlockDto $blockDto)
    {
        $bitcoinBlockDto=new BitcoinBlockDto();

        $bitcoinBlockDto->setSize($blockDto->getBlockSize());
        $bitcoinBlockDto->setPreviousBlockHash(bin2hex($blockDto->getHashPrevBlock()));
        $bitcoinBlockDto->setTime($blockDto->getTime());
        $bitcoinBlockDto->setTransactionsCount(count($blockDto->getTransactions()));

        $blockhash=$this->block_hash($blockDto);
        $bitcoinBlockDto->setHash($blockhash);

        $this->processing_block=$blockhash;

        $transactions=$blockDto->getTransactions();
        $transactionDtos=array();

        $sum_of_inputs=0.0;
        $sum_of_outputs=0.0;
        $sum_of_fees=0.0;

        $height=0; // height genesis bloku
        // pokud se nejedná se o genesis block
        if ($bitcoinBlockDto->getPreviousBlockHash() != self::GENESIS_BLOCK_PREVIOUS_HASH)
        {
            // nastavení zřetězení bloků a height
            $previousBlock = $this->bitcoinBlockModel->existByHash($bitcoinBlockDto->getPreviousBlockHash());
            /*
             * pokud předchozí blok není v databázi, uloží se aktuální blok do nezpracovaných bloků
             * a bude zpracován později až bude předchozí blok uložen v databázi
             */
            if ($previousBlock == null)
            {
                $this->bitcoinOutOfOrderBlockModel->insertBlock($blockhash, $bitcoinBlockDto->getPreviousBlockHash(), $blockDto);
                return $blockhash;
            }
            $height = $previousBlock->getHeight() + 1;
            $previousBlock->setNextBlockHash($bitcoinBlockDto->getHash());
            $this->bitcoinBlockModel->updateBlock($previousBlock);
        }
        $bitcoinBlockDto->setHeight($height);

        foreach ($transactions as $transaction)
        {
            $transactionDto=new BitcoinTransactionDto();
            $txid=$this->transaction_hash($transaction);
            $transactionDto->setTxid($txid);
            $transactionDto->setBlockhash($blockhash);
            $transactionDto->setTime($blockDto->getTime());

            $inputs = $transaction->getInputTransactions();
            $outputs = $transaction->getOutputTransactions();

            $sum_of_transaction_inputs=0.0;
            $sum_of_transaction_outputs=0.0;
            $input_addresses=array();
            $input_clusterize_addresses=array();
            $coinbase=false;

            /**
             * Kolik peněz získala a pozbyla, která adresa, použitá v této transakci
             * [adresa] => změna zůstatku
             */
            $address_balances=array();

            $inputDtos=array();
            // zpracování vstupů transakce
            foreach ($inputs as $input)
            {
               $inputDto=new BitcoinTransactionInputDto();
               $inputDto->setTxid(bin2hex($input->getHash()));
               $inputDto->setVout($input->getIndex());

               // coinbase transaction
               if ($inputDto->getTxid() == 0 && $inputDto->getVout() == self::COINBASE_N)
               {
                   $coinbase=true;
               }
               // normal transaction
               else
               {
                   //spent output transaction
                   $outputDto=$this->bitcoinTransactionModel->findTransactionOutput($inputDto->getTxid(),$inputDto->getVout());
                   $inputDto->setValue($outputDto->getValue());
                   $outputDto->setSpent(true);
                   $outputDto->setSpentTxid($txid);
                   $outputDto->setSpentTs($transactionDto->getTime());

                   $script_sig=$input->getScriptSig();
                   $inputDto->setScriptSig($script_sig);

                   $input_address=$outputDto->getSerializedAddress();
                   if ($input_address != null){
                       $input_addresses[]=$input_address;
                       $inputDto->setSerializedAddress($input_address);
                   }
                   $billable_address = BitcoinUtils::get_billable_address($outputDto->getRedeemerDto());
                   if ($billable_address != null) {

                       $address_balances = $this->add_address_balance($address_balances, BitcoinUtils::get_billable_address($outputDto->getRedeemerDto()), -($outputDto->getValue()));
                       $input_clusterize_addresses[]=$billable_address;
                   }
                   $inputDto->setParsedScriptSig($this->scriptSigParser->parse($script_sig,$outputDto->getRedeemerDto()));

                   $this->bitcoinTransactionModel->updateTransactionOutput($inputDto->getTxid(),$inputDto->getVout(),$outputDto);
                   $sum_of_transaction_inputs += $inputDto->getValue();
               }
               $inputDtos[]=$inputDto;
           }

           $outputDtos=array();
           $n=0; // index výstupu
            // zpracování výstupů transakce
           foreach ($outputs as $output)
           {
               $outputDto = new BitcoinTransactionOutputDto();
               $outputDto->setN($n++);
               $outputDto->setSpent(false);
               $outputDto->setRawValue($output->getValue());

               $script_pubkey=$output->getScriptPubkey();

               $outputDto->setScriptPubkey($script_pubkey);
               $redeemerDto=$this->scriptPubkeyParser->parse($script_pubkey);
               $outputDto->setSerializedAddress(BitcoinUtils::serialize_address($redeemerDto));
               $outputDto->setRedeemerDto($redeemerDto);

               $address_balances = $this->add_address_balance($address_balances,BitcoinUtils::get_billable_address($redeemerDto), $outputDto->getValue());

               $sum_of_transaction_outputs += $outputDto->getValue();
               $outputDtos[]=$outputDto;
           }

            $sum_of_inputs += $sum_of_transaction_inputs;
            $sum_of_outputs += $sum_of_transaction_outputs;

            $transaction_fee=0;
            // v coinbase transakci se nepočítají poplatky
            if (!$coinbase) {
                $transaction_fee = $sum_of_transaction_outputs - $sum_of_transaction_inputs;
                $sum_of_fees += $transaction_fee;
            }
            $transactionDto->setCoinbase($coinbase);
            $transactionDto->setSumOfInputs($sum_of_transaction_inputs);
            $transactionDto->setSumOfOutputs($sum_of_transaction_outputs);
            $transactionDto->setSumOfFees($transaction_fee);
            $transactionDto->setInputs($inputDtos);
            $transactionDto->setOutputs($outputDtos);
            $transactionDto->setUniqueInputAddresses(count(array_unique($input_addresses)));

            // uložení změn stavů na účtech
            $this->bitcoinTransactionModel->storeNode($transactionDto);
            $transactionDtos[]=$transactionDto;

            $this->store_address_balance_changes($address_balances,$txid);
            $this->bitcoinClusterModel->clusterizeAddresses(array_unique($input_clusterize_addresses));
        }
        $bitcoinBlockDto->setSumOfInputs($sum_of_inputs);
        $bitcoinBlockDto->setSumOfOutputs($sum_of_outputs);
        $bitcoinBlockDto->setSumOfFees($sum_of_fees);

        $this->bitcoinBlockModel->storeNode($bitcoinBlockDto);
        $this->bitcoinBlockModel->includeTransactions($bitcoinBlockDto,$transactionDtos);
        $this->bitcoinBlockModel->setFollow($bitcoinBlockDto,bin2hex($blockDto->getHashPrevBlock()));

        // pokud je v databázi nezpracovaný blok, který navazuje na ten aktuální, zpracuj jej
        $outOfOrderBlok=$this->bitcoinOutOfOrderBlockModel->exists($blockhash);
        if ($outOfOrderBlok != null)
        {
            $outOfOrderBlockhahs=$this->process_block($outOfOrderBlok);
            $this->bitcoinOutOfOrderBlockModel->deleteByBlockhash($outOfOrderBlockhahs);
        }
        return $blockhash;
    }

    /**
     * Po zpracování jedné transakce uloží změny stavů na účtech
     * Také k adrese přidá záznam, že participovala na této transakci
     *
     * @param $address_balances array změny na účtech kde index = adresa účtu; hodnota = změna stavu účtu
     * @param $txid string - txid transakce která se právě zpracovává
     */
    private function store_address_balance_changes(array $address_balances, $txid)
    {
        // uložení změn stavů na účtech
        foreach ($address_balances as $address => $balance_change)
        {
            $this->bitcoinAddressModel->addTransactionRecord($address,$txid,$balance_change);
        }
    }

    /**
     * Přidá nebo změní záznam o změně stavu účtu v jedné transakci
     *
     * @param array $address_balance
     * @param $address
     * @param $balance_change
     * @return array
     */
    private function add_address_balance(array $address_balance, $address, $balance_change)
    {
        if ($address == null)
        {
            return $address_balance;
        }

        if (isset($address_balance[$address]))
        {
            $address_balance[$address] += $balance_change;
        }
        else
        {
            $address_balance[$address] = $balance_change;
        }
        return $address_balance;
    }


    /**
     * Vypočítá hash transakce (txid)
     *
     * @param TransactionDto $transactionDto
     */
    private function transaction_hash(TransactionDto $transactionDto)
    {
        $hash = hash("sha256",hash("sha256",$transactionDto->getRawTransaction(),true));
        return $this->SwapOrder($hash);

        // starý způsob jak počítat hash transakce, taky to jde ale když máme raw data transakce, tak to neín třeba
       /* $string="";
        $string.=$this->littleEndian($transactionDto->getVersion());

        $input_transactions=$transactionDto->getInputTransactions();
        $string.=$this->writeVarInt(count($input_transactions));


        foreach ($input_transactions as $input_transaction)
        {
            $string.=bin2hex($input_transaction->getHash());
            $string.=$this->littleEndian($input_transaction->getIndex());
            $string.=$this->writeVarInt($input_transaction->getScriptSigLen());
            $string.=bin2hex($input_transaction->getScriptSig());
            $string.=$this->littleEndian($input_transaction->getSequence());
        }

        $output_transactions=$transactionDto->getOutputTransactions();
        $string.=$this->writeVarInt(count($output_transactions));

        foreach ($output_transactions as $output_transaction)
        {
            $string.=$this->littleEndian64b($output_transaction->getValue());
            $string.=$this->writeVarInt($output_transaction->getScriptPubkeyLen());
            $string.=bin2hex($output_transaction->getScriptPubkey());
        }
        $string.=$this->littleEndian($transactionDto->getLockTime());

        $hash = hash("sha256",hash("sha256",hex2bin($string),true));
        return $this->SwapOrder($hash);*/
    }

    /**
     * Spočítá hash pro bitcoin blok
     *
     * @param BlockDto $blockDto Načtený blok
     * @return string Spočtený hash hlavičky bloku
     */
    private function block_hash(BlockDto $blockDto)
    {
        $version = $this->littleEndian($blockDto->getVersion());
        $prevBlockHash = $this->SwapOrder(bin2hex($blockDto->getHashPrevBlock()));
        $rootHash = $this->SwapOrder(bin2hex($blockDto->getHashMerkleRoot()));
        $time = $this->littleEndian($blockDto->getTime());
        $bits = $this->littleEndian($blockDto->getTarget());
        $nonce = $this->littleEndian($blockDto->getNonce());

        //concat it all
        $header_hex = $version . $prevBlockHash . $rootHash . $time . $bits . $nonce;

        //convert from hex to binary
        $header_bin  = hex2bin($header_hex);
        //hash it then convert from hex to binary
        $pass1 = hex2bin(  hash('sha256', $header_bin )  );
        //Hash it for the seconded time
        $pass2 = hash('sha256', $pass1);
        //fix the order
        $finalHash = $this->SwapOrder($pass2);

        return $finalHash;
    }

    /**
     * Obrátí pořadí bajtů v řetězci - pomocná funkce pro výpočet hash
     *
     * @param $in
     * @return string
     */
    private function SwapOrder($in)
    {
        $Split = str_split(strrev($in));
        $x='';
        for ($i = 0; $i < count($Split); $i+=2) {
            $x .= $Split[$i+1].$Split[$i];
        }
        return $x;
    }

    /**
     * Pomocná funkce pro výpočet hash
     * @param $value
     * @return string
     */
    private function littleEndian($value){
        return implode (unpack('H*',pack("V*",$value)));
    }

    private function littleEndian16b($value)
    {
        return implode (unpack('H*',pack("v*",$value)));
    }

    private function littleEndian64b($value)
    {
        return implode (unpack('H*',pack("P*",$value)));
    }

    /**
     * Zapíše hodnotu ve formátu VarInt
     *
     * @param $value int hodnota, která se má zapsat
     * @return string - hexa string of var int
     */
    private function writeVarInt($value)
    {
        if ($value <= 252)
        {
            $output= str_pad(dechex($value),2,"0",STR_PAD_LEFT);
        }
        else
        {
            if ($value <= pow(2,16)-1)
            {
                $output=dechex(253);
                $output.=str_pad($this->littleEndian16b($value),4,"0",STR_PAD_LEFT);
            }
            elseif($value <= pow(2,32)-1)
            {
                $output=dechex(254);
                $output.=str_pad($this->littleEndian($value),8,"0",STR_PAD_LEFT);
            }
            else
            {
                $output=dechex(255);
                $output.=str_pad($this->littleEndian64b($value),16,"0",STR_PAD_LEFT);
            }
        }

        return $output;
    }

    /**
     * Vymazání všech bloků z databáze
     * Vymaže uloženou pozici parsování
     * Ponechá v databázi adresy, tagy, clustery apod.
     */
    private function clearDatabase()
    {
        $this->positionManager->deletePosition();
        $this->bitcoinBlockModel->deleteAllNodes();
        $this->bitcoinTransactionModel->deleteAllNodes();
        $this->bitcoinAddressModel->clearAddresses();
        $this->bitcoinOutOfOrderBlockModel->deleteAllNodes();
    }

    /**
     * Smaže úplně všechny údaje z databáze
     */
    private function totalClearDatabase()
    {
        $this->positionManager->deletePosition();
        $this->bitcoinBlockModel->deleteAllNodes();
        $this->bitcoinTransactionModel->deleteAllNodes();
        $this->bitcoinAddressModel->deleteAllNodes();
        $this->bitcoinOutOfOrderBlockModel->deleteAllNodes();
        $this->bitcoinClusterModel->deleteAllNodes();
        $this->bitcoinTagModel->deleteAllNodes();
    }

    /**
     * Připraví databázi na běh CCA
     */
    private function initDatabase()
    {
        $this->bitcoinBlockModel->createIndexes();
        $this->bitcoinTransactionModel->createIndexes();
        $this->bitcoinAddressModel->createIndexes();
    }
}