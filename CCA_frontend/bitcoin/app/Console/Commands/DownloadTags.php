<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Model\BitcoinAddressModel;
use Goutte\Client;
use Stringy\Stringy as S;

/**
 * CLI uloha realizujuca stahovanie tagov Bitcoinovych adres.
 *
 * Class DownloadTags
 * @package App\Console\Commands
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class DownloadTags extends Command
{
    /**
     * Nazov a popis prikazu.
     *
     * @var string
     */
    protected $signature = 'tags:download';

    /**
     * Popis prikazu.
     *
     * @var string
     */
    protected $description = 'Address tag downloader.';
    /**
     * Zdroje Bitcoin tagov.
     * @var array
     */
    protected $tagSources;
    /**
     * HTTP client
     * @var Client
     */
    protected $client;

    protected $debug = false;
    /**
     * Vytvorenie novej instancie.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->client       = new Client();
        $this->tagSources   = [
            //submitted links
            'https://blockchain.info/tags?filter=8',
            //signed messages
            'https://blockchain.info/tags?filter=16',
            //bitcoin talk profiles
            'https://blockchain.info/tags?filter=2',
            //bitcoin otc profiles
            'https://blockchain.info/tags?filter=4'
        ];
    }

    /**
     * Vykonavanie ulohy.
     *
     * @return mixed
     */
    public function handle()
    {
        $newRecords = 0;
        foreach($this->tagSources as $tagSource){
            if($this->debug){
                $this->line("Stahujem zo zdroja: {$tagSource}.");
            }
            $offset = 0;
            do{
                $crawler = $this->client->request('GET', $tagSource.'&offset='.$offset);
                $numberOfRows = count($crawler->filter('tr'));
                /**
                 * @var $tr \DOMElement
                 */
                foreach ($crawler->filter('tr') as $tr) {
                    $row     = S::create($tr->nodeValue)->split('\n');
                    $address = (string)$row[0]->trim();
                    $tag     = (string) $row[1]->trim();
                    $url     = (string) $row[2]->trim();
                    $addrModel = new BitcoinAddressModel($address);
                    if($addrModel->hasTag($tag, $url, BitcoinAddressModel::BLOCKCHAININFO_SOURCE)){
                        break;
                    }
                    //Pridanie tagu k adrese
                    $addrModel->addTag($tag, $url, BitcoinAddressModel::BLOCKCHAININFO_SOURCE);
                    ++$newRecords;
                }
                $moreData = $numberOfRows > 0;
                $offset += 200;
            }while($moreData);
        }
        if($this->debug){
            $this->line("Pridanych {$newRecords} zaznamov.");
        }
    }
}
