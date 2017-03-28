<?php

namespace App\Console\Commands;


use App\Console\Exceptions\FileNotReadableException;
use App\Model\Bitcoin\BitcoinAddressModel;
use App\Model\Bitcoin\BitcoinTagModel;
use App\Model\Bitcoin\Dto\BitcoinTagDto;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Class AddTag
 * @package App\Console\Commands
 *
 * Class for adding tags to database and pair it with addresses
 * TODO: this feature does not work, this class is only template for further extension
 *
 * Few notes about this system
 *  - if you add one tag to same address several times, it will add it only once, no need to worry about this
 *  - !! however it is possible to insert multiple tags with same text, if you do this,
 *       it will be independent instances, and will not be connected
 *  - if you want to add more field to store with tag then:
 *      - go to class  App\Model\Bitcoin\Dto\BitcoinTagDto  And add field that you want to store
 *      - next go to class App\Model\Bitcoin\BitcoinTagModel And modify methods  "array_to_dto" and "dto_to array" to load and store this new values
 *      - at last go to view file resources/views/tags/findOne.blade.php  and add displaying this new fields
 */
class AddTag extends Command
{
    /**
     * Defines how is this command run from command line
     * @var string
     */
    protected $signature = 'tags:add 
        {file? : Path to file which contains information to be parsed}
        {--clearTags : Remove all tags from database}
        {--runExample : Add example tag to database}'; // TODO: remove this option

    /**
     * Text description of this command
     * @var string
     */
    protected $description = "Parse tags from input file and store them to database";


    /**
     * Model for managing bitcoin addresses
     * @var BitcoinAddressModel
     */
    private $bitcoinAddressModel;

    /**
     * Model for managing tags in bitcoin system
     * @var BitcoinTagModel
     */
    private $bitcoinTagModel;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("start: ".(string)Carbon::now());
        $this->bitcoinAddressModel=BitcoinAddressModel::getInstance();
        $this->bitcoinTagModel=BitcoinTagModel::getInstance();

        if ($this->option("clearTags"))
        {
            $this->deleteTags();
            return;
        }

        // TODO: remove this block
        if ($this->option("runExample"))
        {
            $this->exampleAddTag();
            $this->exampleAddTagToAddress();
            $this->exampleNewTagToAddress();
        }

        $pathToFile=$this->argument("file");
        if (!empty($pathToFile))
        {
            // read entire input file into string
            $fileContent = file_get_contents($pathToFile);
            if ($fileContent === FALSE)
            {
                throw new FileNotReadableException("Unable to read file: ".$pathToFile);
            }

            /**
             * TODO: write your code here
             */
        }

        $this->info("End: ".(string)Carbon::now());
    }

    /**
     * Delete all tag nodes from database
     */
    private function deleteTags()
    {
        $this->bitcoinTagModel->deleteAllNodes();
    }


    /**
     * Example of how a tag can be created
     */
    private function exampleAddTag()
    {
        $tagDto=new BitcoinTagDto();
        $tagDto->setTag("Text of tag");
        $tagDto->setUrl("http://www.urlLeadingToTag.net");

        $this->bitcoinTagModel->insertTag($tagDto);
    }

    /**
     * Example of how to add existing tag to address
     */
    private function exampleAddTagToAddress()
    {
        $tagDto = $this->bitcoinTagModel->existByTag("Text of tag")[0];
        $addressDto = $this->bitcoinAddressModel->findByAddress("15NUwyBYrZcnUgTagsm1A7M2yL2GntpuaZ");

        $this->bitcoinAddressModel->addTag($addressDto,$tagDto);
    }

    /**
     * Example how to create new tag and add it to address
     */
    private function exampleNewTagToAddress()
    {
        $tagDto=new BitcoinTagDto();
        $tagDto->setTag("New tag");
        $tagDto->setUrl("http://UrlOfTag.net");

        $new_tag=$this->bitcoinTagModel->insertTag($tagDto);
        $addressDto = $this->bitcoinAddressModel->findByAddress("15NUwyBYrZcnUgTagsm1A7M2yL2GntpuaZ");


        $this->bitcoinAddressModel->addTag($addressDto,$new_tag);
    }
}