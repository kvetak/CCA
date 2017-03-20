<?php

namespace App\Model\Bitcoin;

use App\Model\Blockchain\Parser\BlockDto;

/**
 * Model pro ukládání bloků, které se v blockchainu naparsují mimo pořadí
 *
 * Class BitcoinOutOfOrderBlockModel
 * @package App\Model\Bitcoin
 */
class BitcoinOutOfOrderBlockModel extends BaseBitcoinModel
{
    const NODE_NAME="out_of_order_block";

    const DB_BLOCKHASH="blockhash",
        DB_PREVIOUS_BLOCKHASH="prev_blockhash",
        DB_BLOCK_DTO="block_dto";

    private static $instance;

    /**
     * Tovární metoda, vrací instanci třídy
     *
     * @return BitcoinOutOfOrderBlockModel volané třídy
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

    /**
     * Vloží blok do databáze
     * @param $blockhash string Hash tohoto bloku
     * @param $previous_blockhash string hash předchozího bloku
     * @param BlockDto $blockDto Dto obsahující informace o bloku
     */
    public function insertBlock($blockhash, $previous_blockhash, BlockDto $blockDto)
    {
        $this->insert(array(
            self::DB_BLOCKHASH => $blockhash,
            self::DB_PREVIOUS_BLOCKHASH => $previous_blockhash,
            self::DB_BLOCK_DTO => $this->input_output_encode($blockDto)
        ));
    }

    /**
     * Vymaže blok z databáze
     * Pokud blok s daným hashem neexistuje, nestane se nic
     *
     * @param $blockhash string Hash bloku který se má smazat
     */
    public function deleteByBlockhash($blockhash)
    {
        $this->delete(self::DB_BLOCKHASH,$blockhash);
    }

    /**
     * Vymaže všechny nezpracované bloky z databáze
     */
    public function deleteAllNodes()
    {
        $this->deleteAll();
    }

    /**
     * Ověří existenci bloku v databázi
     * Pokud existuje vrátí jeho dto
     *
     * @param $previous_blockhash
     * @return BlockDto|null
     */
    public function exists($previous_blockhash)
    {
        $data=$this->findOne(self::DB_PREVIOUS_BLOCKHASH,$previous_blockhash);
        if (count($data) == 0){
            return null;
        }
        return $this->input_output_decode($data[self::DB_BLOCK_DTO]);
    }

}