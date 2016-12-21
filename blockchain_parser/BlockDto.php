<?php
/**
 * User: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 15:12
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class BlockDto
 * @package BlockChainParser
 *
 * Data transfer object for one block of blockchain
 */
class BlockDto
{
    /**
     * @var Integer - Block format version
     */
    private $version;

    /**
     * @var Hash - Hash of previous block header
     */
    private $hashPrevBlock;

    /**
     * @var Hash - Hash of the Merkle tree
     */
    private $hashMerkleRoot;

    /**
     * @var Integer - timestamp of block creation time
     */
    private $time;

    /**
     * @var Integer - Compact format value of target for proof of work
     */
    private $target;

    /**
     * @var Integer - Nonce for proof of work
     */
    private $nonce;

    /**
     * @var array<TransactionDto> - Array of all transactions in block
     */
    private $transactions;

    /**
     * @var Integer -  4B of magic bytes, present in blockchain before this block
     */
    private $magicBytes;

    /**
     * @var Integer - 4B of block size, present in blockchain before this block
     */
    private $blockSize;

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
     * @return Hash
     */
    public function getHashPrevBlock()
    {
        return $this->hashPrevBlock;
    }

    /**
     * @param Hash $hashPrevBlock
     */
    public function setHashPrevBlock($hashPrevBlock)
    {
        $this->hashPrevBlock = $hashPrevBlock;
    }

    /**
     * @return Hash
     */
    public function getHashMerkleRoot()
    {
        return $this->hashMerkleRoot;
    }

    /**
     * @param Hash $hashMerkleRoot
     */
    public function setHashMerkleRoot($hashMerkleRoot)
    {
        $this->hashMerkleRoot = $hashMerkleRoot;
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
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param mixed $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return int
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param int $nonce
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
    }

    /**
     * @return array<TransactionDto>
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array<TransactionDto> $transactions
     */
    public function setTransactions(array $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return int
     */
    public function getMagicBytes()
    {
        return $this->magicBytes;
    }

    /**
     * @param int $magicBytes
     */
    public function setMagicBytes($magicBytes)
    {
        $this->magicBytes = $magicBytes;
    }

    /**
     * @return int
     */
    public function getBlockSize()
    {
        return $this->blockSize;
    }

    /**
     * @param int $blockSize
     */
    public function setBlockSize($blockSize)
    {
        $this->blockSize = $blockSize;
    }


    public function to_string()
    {
        $string="magic bytes: ".$this->magicBytes.PHP_EOL.
            "block size: ".$this->blockSize.PHP_EOL.
            "version: ".$this->version.PHP_EOL.
            "hash previous block: ".bin2hex($this->hashPrevBlock).PHP_EOL.
            "hash merkle tree: ".bin2hex($this->hashMerkleRoot).PHP_EOL.
            "time: 0x".dechex($this->time).PHP_EOL.
            "time: ".date("Y-m-d - H:i:s",$this->time).PHP_EOL.
            "target(bits): ".dechex($this->target).PHP_EOL.
            "nonce: ".($this->nonce).PHP_EOL.
            "nonce: 0x".dechex($this->nonce).PHP_EOL;

        foreach ($this->transactions as $transaction)
        {
            $string.=$transaction->to_string();
        }
        return $string;
    }
}