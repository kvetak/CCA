<?php
/**
 * @file: BitcoinTransactionDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;

class BitcoinTransactionDto
{
    /**
     * Jednoznačný identifikátor transakce
     * @var int
     */
    private $txid;

    /**
     * Hash bloku, do kterého transakce patří
     * @var Hash
     */
    private $blockhash;

    /**
     * Čas vzniku transakce
     * @var int - unix timestamp
     */
    private $time;

    /**
     * Indikátor jestli je transakce coinbase
     * @var bool
     */
    private $coinbase;

    /**
     * Vstupy transakce
     * @var array
     */
    private $inputs;

    /**
     * Výstupy transakce
     * @var array
     */
    private $outputs;

    /**
     * Součet hodnot všech vstupních transakcí
     * @var double
     */
    private $sum_of_inputs;

    /**
     * Součet hodnot všech výstupních transakcí
     * @var double
     */
    private $sum_of_outputs;

    /**
     * Součet hodnot všech poplatků
     * @var double
     */
    private $sum_of_fees;

    /**
     * Počet unikátních adres, použitých na vstupech transakcí
     * @var int
     */
    private $unique_input_addresses;

    /**
     * @return int
     */
    public function getTxid()
    {
        return $this->txid;
    }

    /**
     * @param int $txid
     */
    public function setTxid($txid)
    {
        $this->txid = $txid;
    }

    /**
     * @return Hash
     */
    public function getBlockhash()
    {
        return $this->blockhash;
    }

    /**
     * @param Hash $blockhash
     */
    public function setBlockhash($blockhash)
    {
        $this->blockhash = $blockhash;
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
        $this->time = $time;
    }

    /**
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @param array $inputs
     */
    public function setInputs($inputs)
    {
        $this->inputs = $inputs;
    }

    /**
     * @return array
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * @param array $outputs
     */
    public function setOutputs($outputs)
    {
        $this->outputs = $outputs;
    }

    /**
     * @return double
     */
    public function getSumOfInputs()
    {
        return $this->sum_of_inputs;
    }

    /**
     * @param double $sum_of_inputs
     */
    public function setSumOfInputs($sum_of_inputs)
    {
        $this->sum_of_inputs = $sum_of_inputs;
    }

    /**
     * @return double
     */
    public function getSumOfOutputs()
    {
        return $this->sum_of_outputs;
    }

    /**
     * @param double $sum_of_outputs
     */
    public function setSumOfOutputs($sum_of_outputs)
    {
        $this->sum_of_outputs = $sum_of_outputs;
    }

    /**
     * @return double
     */
    public function getSumOfFees()
    {
        return $this->sum_of_fees;
    }

    public function getRoundedSumOfFees()
    {
        return round($this->sum_of_fees,2);
    }

    /**
     * @param double $sum_of_fees
     */
    public function setSumOfFees($sum_of_fees)
    {
        $this->sum_of_fees = $sum_of_fees;
    }

    /**
     * @return int
     */
    public function getUniqueInputAddresses()
    {
        return $this->unique_input_addresses;
    }

    /**
     * @param int $unique_input_addresses
     */
    public function setUniqueInputAddresses($unique_input_addresses)
    {
        $this->unique_input_addresses = $unique_input_addresses;
    }

    /**
     * @return bool
     */
    public function isCoinbase()
    {
        return $this->coinbase;
    }

    /**
     * @param bool $coinbase
     */
    public function setCoinbase($coinbase)
    {
        $this->coinbase = $coinbase;
    }
}