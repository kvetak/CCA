<?php
/**
 * User: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 15:04
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class BlockParser
 * @package BlockChainParser
 *
 * This class realizes parsing of blockchain blocks.
 * It reads data from FileReader and parse it into block dtos.
 */
class BlockParser
{
    /**
     * @var FileReader - handler of input file
     */
    private $fileReader;

    /**
     * @var TypeReader - Reader for specific data types from input file
     */
    private $typeReader;

    /**
     * BlockParser constructor.
     * @param FileReader $fileReader
     */
    public function __construct(FileReader $fileReader)
    {
        $this->fileReader = $fileReader;
        $this->typeReader = new TypeReader($fileReader);
    }

    /**
     * @brief Parse one block from blockchain
     * @return BlockDto - Dto containing parsed block
     */
    public function parseBlock()
    {
        $blockDto=new BlockDto();
        $this->parseBlockHeader($blockDto);
        $this->parseTransactions($blockDto);
        return $blockDto;
    }

    /**
     * @brief header of block
     * @param BlockDto $blockDto - Dto to store values in
     *
     * Parse magic bytes, block size and values from block header and store it to given DTO
     * number of transactions is left in input
     */
    private function parseBlockHeader(BlockDto $blockDto)
    {
        $blockDto->setMagicBytes($this->typeReader->readUInt());
        $blockDto->setBlockSize($this->typeReader->readUInt());
        $blockDto->setVersion($this->typeReader->readUInt());
        $blockDto->setHashPrevBlock($this->typeReader->readHash());
        $blockDto->setHashMerkleRoot($this->typeReader->readHash());
        $blockDto->setTime($this->typeReader->readUInt());
        $blockDto->setTarget($this->typeReader->readUInt());
        $blockDto->setNonce($this->typeReader->readUInt());
    }

    /**
     * @brief Parse transactions for one block
     * @param BlockDto $blockDto - DTO to store transactions in
     *
     * Parse number of transactions from file and then parse all of the transactions
     */
    private function parseTransactions(BlockDto $blockDto)
    {
        $transactionCount=$this->typeReader->readVarInt();
        $transactions=array();
        for ($i=0 ; $i < $transactionCount ; $i++)
        {
            $transactions[]=$this->parseTransaction();
        }
        $blockDto->setTransactions($transactions);
    }

    /**
     * @brief Parse one transaction from block
     * @return TransactionDto parsed transaction
     */
    private function parseTransaction()
    {
        // store all raw data of transaction into buffer
        $this->fileReader->start_buffer();

        $transactionDto=new TransactionDto();
        $transactionDto->setVersion($this->typeReader->readUInt());

        // parse transaction's inputs
        $inputCount=$this->typeReader->readVarInt();
        $inputs=array();
        for ($i=0 ; $i < $inputCount ; $i++)
        {
            $inputs[]=$this->parseTransactionInput();
        }
        $transactionDto->setInputTransactions($inputs);

        // parse transaction's outputs
        $outputCount=$this->typeReader->readVarInt();
        $outputs=array();
        for ($i=0 ; $i < $outputCount ; $i++)
        {
            $outputs[]=$this->parseTransactionOutput();
        }
        $transactionDto->setOutputTransactions($outputs);

        $transactionDto->setLockTime($this->typeReader->readUInt());

        // copy buffer to transaction
        $this->fileReader->end_buffer();
        $transactionDto->setRawTransaction($this->fileReader->get_buffer());

        return $transactionDto;
    }

    /**
     * @brief Parse one transaction input
     * @return InputTransactionDto - parsed transaction input
     */
    private function parseTransactionInput()
    {
        $input=new InputTransactionDto();
        $input->setHash($this->typeReader->readHash());
        $input->setIndex($this->typeReader->readUInt());
        $script_len=$this->typeReader->readVarInt();
        $input->setScriptSigLen($script_len);
        $input->setScriptSig($this->typeReader->readCScript($script_len));
        $input->setSequence($this->typeReader->readUInt());
        return $input;
    }

    /**
     * @brief Parse one transaction output
     * @return OutputTransactionDto - parsed transaction output
     */
    private function parseTransactionOutput()
    {
        $output=new OutputTransactionDto();
        $output->setValue($this->typeReader->read64Int());
        $script_len=$this->typeReader->readVarInt();
        $output->setScriptPubkeyLen($script_len);
        $output->setScriptPubkey($this->typeReader->readCScript($script_len));
        return $output;
    }

}