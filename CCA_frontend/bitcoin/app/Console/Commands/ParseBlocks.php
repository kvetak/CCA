<?php
/**
 * @file: ImportBlocks.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Console\Commands;

use App\Model\Blockchain\Parser\BlockchainParser;
use App\Model\Blockchain\Parser\PositionDto;
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("start: ".(string)Carbon::now());
        $this->positionManager=new PositionManager();

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

        return null;




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

        echo $parser->parse(10000)[0]->to_string();



        $this->positionManager->store($parser->getPosition());
    }


    /**
     * Vymazání všech bloků z databáze
     */
    private function clearDatabase()
    {
        $this->positionManager->deletePosition();
    }
}