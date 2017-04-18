<?php
/**
 * @file: BitcoinBlockDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;

class BitcoinBlockDto
{
    /**
     * Hash tohoto bitcoin bloku
     * @var hash
     */
    private $hash;

    /**
     * Hash předchozího bloku v blockchainu
     * @var hash
     */
    private $previous_block_hash;

    /**
     * Hash následujícího bloku v blockchainu
     * @var hash
     */
    private $next_block_hash;

    /**
     * Blocksize - velikost bloku v bajtech
     * @var int
     */
    private $size;

    /**
     * Číslo bloku v blockchainu (height)
     * @var int
     */
    private $height;

    /**
     * Čas vzniku bloku
     * @var int - unix timestamp
     */
    private $time;

    /**
     * Suma všech vstupních transakcí
     * @var float
     */
    private $sum_of_inputs;

    /**
     * Suma všech výstupních transakcí
     * @var float
     */
    private $sum_of_outputs;

    /**
     * Suma všech poplatků
     * @var float
     */
    private $sum_of_fees;

    /**
     * Počet transakcí
     * @var int
     */
    private $transactions_count;

    /**
     * @return hash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param hash $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return hash
     */
    public function getPreviousBlockHash()
    {
        return $this->previous_block_hash;
    }

    /**
     * @param hash $previous_block_hash
     */
    public function setPreviousBlockHash($previous_block_hash)
    {
        $this->previous_block_hash = $previous_block_hash;
    }

    /**
     * @return hash
     */
    public function getNextBlockHash()
    {
        return $this->next_block_hash;
    }

    /**
     * @param hash $next_block_hash
     */
    public function setNextBlockHash($next_block_hash)
    {
        $this->next_block_hash = $next_block_hash;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime($time)
    {
        $this->time = intval($time);
    }

    /**
     * @return float
     */
    public function getSumOfInputs()
    {
        return $this->sum_of_inputs;
    }

    /**
     * @param float $sum_of_inputs
     */
    public function setSumOfInputs($sum_of_inputs)
    {
        $this->sum_of_inputs = $sum_of_inputs;
    }

    /**
     * @return float
     */
    public function getSumOfOutputs()
    {
        return $this->sum_of_outputs;
    }

    /**
     * @param float $sum_of_outputs
     */
    public function setSumOfOutputs($sum_of_outputs)
    {
        $this->sum_of_outputs = $sum_of_outputs;
    }

    /**
     * @return float
     */
    public function getSumOfFees()
    {
        return $this->sum_of_fees;
    }

    /**
     * @param float $sum_of_fees
     */
    public function setSumOfFees($sum_of_fees)
    {
        $this->sum_of_fees = $sum_of_fees;
    }

    public function getRoundedSumOfFees()
    {
        return round($this->sum_of_fees,2);
    }

    /**
     * @return int
     */
    public function getTransactionsCount()
    {
        return $this->transactions_count;
    }

    /**
     * @param int $transactions_count
     */
    public function setTransactionsCount($transactions_count)
    {
        $this->transactions_count = $transactions_count;
    }
}