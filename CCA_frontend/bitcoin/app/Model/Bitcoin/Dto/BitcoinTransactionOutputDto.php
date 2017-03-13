<?php
/**
 * @file: BitcoinTransactionOutputDto.php
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model\Bitcoin\Dto;

use App\Model\Bitcoin\ScriptParser\Dto\BitcoinScriptRedeemerDto;

/**
 * DTO pro jeden výstup transakce
 *
 * Class BitcoinTransactionOutputDto
 * @package App\Model\Bitcoin\Dto
 */
class BitcoinTransactionOutputDto
{
    const SATOSHIS_COEFICIENT=0.00000001;

    /**
     * Rozparsovaný script pubkey který určuje, kdo může tento výstup použít
     * @var BitcoinScriptRedeemerDto
     */
    private $redeemer_dto;

    /**
     * script pubkey výstupu transakce v raw podobě
     * @var string
     */
    private $script_pubkey;

    /**
     * Množství BTC utracených v tomto výstupu
     * @var double
     */
    private $value;

    /**
     * Číslo (index) výstupu transakce
     * @var int
     */
    private $n;

    /**
     * Je tento výstup použitý
     * @var bool
     */
    private $spent;

    /**
     * Transakce ve které je tento výstup použit
     * @var int
     */
    private $spentTxid;

    /**
     * Čas použití tohoto výstupu
     * @var int - unix timestamp
     */
    private $spentTs;

    /**
     * Serializovaná výstupní adresa
     * @var string
     */
    private $outputAddress;

    /**
     * @return BitcoinScriptRedeemerDto
     */
    public function getRedeemerDto()
    {
        return $this->redeemer_dto;
    }

    /**
     * @param BitcoinScriptRedeemerDto $redeemer_dto
     */
    public function setRedeemerDto($redeemer_dto)
    {
        $this->redeemer_dto = $redeemer_dto;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Hodnota value je vložena v Satoshis kódování (nativní pro blockchain) a je třeba ji zformátovat
     * @param float $value
     */
    public function setRawValue($value)
    {
        $this->value = $value * self::SATOSHIS_COEFICIENT;
    }

    /**
     * Hodnota value je již zformátovaná a není nutné ji převádět
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value=$value;
    }

    /**
     * @return int
     */
    public function getN()
    {
        return $this->n;
    }

    /**
     * @param int $n
     */
    public function setN($n)
    {
        $this->n = $n;
    }

    /**
     * @return bool
     */
    public function isSpent()
    {
        return $this->spent;
    }

    /**
     * @param bool $spent
     */
    public function setSpent($spent)
    {
        $this->spent = $spent;
    }

    /**
     * @return int
     */
    public function getSpentTxid()
    {
        return $this->spentTxid;
    }

    /**
     * @param int $spentTxid
     */
    public function setSpentTxid($spentTxid)
    {
        $this->spentTxid = $spentTxid;
    }

    /**
     * @return int
     */
    public function getSpentTs()
    {
        return $this->spentTs;
    }

    /**
     * @param int $spentTs
     */
    public function setSpentTs($spentTs)
    {
        $this->spentTs = $spentTs;
    }

    /**
     * @return string
     */
    public function getScriptPubkey()
    {
        return $this->script_pubkey;
    }

    /**
     * @param string $script_pubkey
     */
    public function setScriptPubkey($script_pubkey)
    {
        $this->script_pubkey = $script_pubkey;
    }

    /**
     * @return string
     */
    public function getOutputAddress()
    {
        return $this->outputAddress;
    }

    /**
     * @param string $outputAddress
     */
    public function setOutputAddress($outputAddress)
    {
        $this->outputAddress = $outputAddress;
    }
}