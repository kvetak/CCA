<?php
/**
 * @file: ImportBlocks.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Console\Commands;

use App\Model\Bitcoin\BitcoinBlockModel;
use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\Bitcoin\Dto\BitcoinBlockDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionInputDto;
use App\Model\Bitcoin\Dto\BitcoinTransactionOutputDto;
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

    /**
     * Název a popis konzolového příkazu
     * @var string
     */
    protected $signature = 'blocks:parse 
        {path? : Path to directory, where blockchain is located}
        {--clear : Delete blockchain in database and clear last parsing location}
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
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("start: ".(string)Carbon::now());
        $this->positionManager=new PositionManager();
        $this->bitcoinBlockModel=new BitcoinBlockModel();
        $this->bitcoinTransactionModel=new BitcoinTransactionModel();

        if ($this->option("clear"))
        {
            $this->clearDatabase();
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

        $blocks= $parser->parse(10); // TODO: nastavit na hodnotu zadanou na vstupu

        foreach($blocks as $block)
        {
            $this->process_block($block);
        }

        $this->positionManager->store($parser->getPosition());
    }

    /**
     * Zpracuj celý načtený blok
     * Vytáhne z bloku veškeré užitečné informace a uloží je do blockchainu
     *
     * @param BlockDto $blockDto Načtený blok
     */
    private function process_block(BlockDto $blockDto)
    {
//        print_r($blockDto);

        $bitcoinBlockDto=new BitcoinBlockDto();

        $bitcoinBlockDto->setSize($blockDto->getBlockSize());
        $bitcoinBlockDto->setPreviousBlockHash(bin2hex($blockDto->getHashPrevBlock()));
        $bitcoinBlockDto->setTime($blockDto->getTime());
        $bitcoinBlockDto->setTransactionsCount(count($blockDto->getTransactions()));

        $blockhash=$this->block_hash($blockDto);
        $bitcoinBlockDto->setHash($blockhash);

        $transactions=$blockDto->getTransactions();

        $sum_of_inputs=0.0;
        $sum_of_outputs=0.0;
        $sum_of_fees=0.0;
        $unique_inputs=0;

        $height=0; // height genesis bloku
        // nejedná se o genesis block
        if ($bitcoinBlockDto->getPreviousBlockHash() != 0)
        {
            // nastavení zřetězení bloků a height
            $previousBlock = $this->bitcoinBlockModel->findByHash($bitcoinBlockDto->getPreviousBlockHash());
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
            $transactionDto->setBlocktime($blockDto->getTime());

            $inputs = $transaction->getInputTransactions();
            $outputs = $transaction->getOutputTransactions();

            $sum_of_transaction_inputs=0.0;
            $sum_of_transaction_outputs=0.0;
            $coinbase=false;


           $inputDtos=array();

           foreach ($inputs as $input)
           {
               $inputDto=new BitcoinTransactionInputDto();
               $inputDto->setTxid($input->getHash());
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

                   // TODO: dopočítat adresy do vstupního dto
                   $this->bitcoinTransactionModel->updateTransactionOutput($inputDto->getTxid(),$inputDto->getVout(),$outputDto);
                   $sum_of_transaction_inputs += $inputDto->getValue();
               }
               $inputDtos[]=$inputDto;
           }

           $outputDtos=array();

           $n=0;
           foreach ($outputs as $output)
           {
               $outputDto = new BitcoinTransactionOutputDto();
               $outputDto->setN($n++);
               $outputDto->setSpent(false);
               $outputDto->setValue($output->getValue());

               $sum_of_transaction_outputs += $outputDto->getValue();
               // TODO: dopočítat adrey do výstupního dto
               $outputDtos[]=$outputDto;
           }

            $sum_of_inputs += $sum_of_transaction_inputs;
            $sum_of_outputs += $sum_of_transaction_outputs;

            // v coinbase transakci s enepočítají poplatky
            if (!$coinbase) {
                $transaction_fee = $sum_of_transaction_outputs - $sum_of_transaction_inputs;
                $sum_of_fees += $transaction_fee;
            }

            $this->bitcoinTransactionModel->storeNode($transactionDto);
        }

        $bitcoinBlockDto->setSumOfInputs($sum_of_inputs);
        $bitcoinBlockDto->setSumOfOutputs($sum_of_outputs);
        $bitcoinBlockDto->setSumOfFees($sum_of_fees);

        $this->bitcoinBlockModel->storeNode($bitcoinBlockDto);
    }

    /**
     * Vypočítá hash transakce (txid)
     *
     * @param TransactionDto $transactionDto
     */
    private function transaction_hash(TransactionDto $transactionDto)
    {
        $string="";
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
        return $this->SwapOrder($hash);
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


        /*$version = $this->littleEndian(1);
        $prevBlockHash = $this->SwapOrder("0000000000000000000000000000000000000000000000000000000000000000");
        $rootHash = $this->SwapOrder("4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b");
        $time = $this->littleEndian(1231006505);
        $bits = $this->littleEndian(486604799);
        $nonce = $this->littleEndian(2083236893);*/

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
     */
    private function clearDatabase()
    {
        $this->positionManager->deletePosition();
        $this->bitcoinBlockModel->deleteAllNodes();
        $this->bitcoinTransactionModel->deleteAllNodes();
    }
}