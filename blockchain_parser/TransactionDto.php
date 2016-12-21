<?php
/**
 * User: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 15:22
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class TransactionDto
 * @package BlockChainParser
 *
 * Data transfer object for one transaction stored in block
 */
class TransactionDto
{
    /**
     * @var Integer - transaction format version
     */
    private $version;

    /**
     * @var Integer - Lock time timestamp, before this time, transactions can be replaced
     */
    private $lockTime;

    /**
     * @var array<InputTransactionDto> - Array of input transactions
     */
    private $inputTransactions;

    /**
     * @var array<OutputTransactionDto> - Array of output transactions
     */
    private $outputTransactions;

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return int
     */
    public function getLockTime()
    {
        return $this->lockTime;
    }

    /**
     * @param int $lockTime
     */
    public function setLockTime($lockTime)
    {
        $this->lockTime = $lockTime;
    }

    /**
     * @return array
     */
    public function getInputTransactions()
    {
        return $this->inputTransactions;
    }

    /**
     * @param array $inputTransactions
     */
    public function setInputTransactions(array $inputTransactions)
    {
        $this->inputTransactions = $inputTransactions;
    }

    /**
     * @return array
     */
    public function getOutputTransactions()
    {
        return $this->outputTransactions;
    }

    /**
     * @param array $outputTransactions
     */
    public function setOutputTransactions(array $outputTransactions)
    {
        $this->outputTransactions = $outputTransactions;
    }




    public function to_string()
    {
        $string="transaction version: ".$this->version.PHP_EOL.
            "lock time: ".$this->lockTime.PHP_EOL.
            "inputs: ".PHP_EOL;
        foreach ($this->inputTransactions as $input)
        {
            $string.=$input->to_string();
        }
        $string.="outputs: ".PHP_EOL;
        foreach ($this->outputTransactions as $output)
        {
            $string.=$output->to_string();
        }
        return $string;
    }
}