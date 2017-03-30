<?php

namespace App\Model\Bitcoin\ScriptParser;

use App\Model\Bitcoin\BitcoinLib;
use App\Model\Bitcoin\ScriptParser\Dto\BitcoinScriptRedeemerDto;

/**
 *
 *
 * Class ScriptPubkeyParser
 * @package App\Model\Bitcoin
 */
class ScriptPubkeyParser extends BaseScriptParser
{
    /**
     * Parsuje položku script z výstupu transakce.
     * Pokud je použit některý z běžných způsobů platby, pak výstupem je typ platby a pokud je to možné,
     * tak adresy na které je platba provedena.
     * Pokud je použit neznámý způsob platby pak je vrácena pouze informace o neznámém způsobu
     *
     * @param $scriptPubkey String - Script field from output of transaction
     * @return BitcoinScriptRedeemerDto - Rozparsovaný script
     */
    public function parse($scriptPubkey)
    {
        $first_byte=$scriptPubkey[0];

        // pay to multisig
        if (ord($first_byte) >= ord($this->OP_PUSH_NUMBER_1) and ord($first_byte) <= ord($this->OP_NUMBER_END))
        {
            return $this->parse_as_multisig($scriptPubkey);
        }

        switch ($first_byte){
            // pay to public key (P2PK)
            case $this->OP_PUSH_65B:
                return $this->parse_as_p2pk($scriptPubkey);

            // pay to public key hash (P2PKH)
            case $this->OP_DUP:
                return $this->parse_as_p2pkh($scriptPubkey);

            // pay to script hash (P2SH)
            case $this->OP_HASH160:
                return $this->parse_as_p2sh($scriptPubkey);

            // nulldata transaction
            case $this->OP_RETURN:
                return $this->parse_as_nulldata($scriptPubkey);

            // neznámý (nestandardní) script
            default:
                return $this->unknownScript();
        }

    }

    /**
     * Parsuje script jako platbu na public key
     *
     * @param $scriptPubkey
     * @return BitcoinScriptRedeemerDto výsledek parsování
     */
    private function parse_as_p2pk($scriptPubkey)
    {
        $length = ord($scriptPubkey[0]);
        $publickey = substr($scriptPubkey,1,$length);

        // check that on the end of script is checksig
        if (substr($scriptPubkey,$length+1) != $this->OP_CHECKSIG)
        {
            return $this->unknownScript();
        }

        $hex_publickey=bin2hex($publickey);

        return new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::PAY_TO_PUBKEY,
            array(BitcoinLib::get_address_from_pubkey($hex_publickey)),
            array($hex_publickey)
        );
    }

    /**
     * Parsuje script jako platbu na hash public key
     *
     * @param $scriptPubkey
     * @return BitcoinScriptRedeemerDto - výsledek parsování
     */
    private function parse_as_p2pkh($scriptPubkey)
    {
        if (substr($scriptPubkey,0,2) != $this->OP_DUP.$this->OP_HASH160)
        {
            return $this->unknownScript();
        }

        $hash_length=ord(substr($scriptPubkey,2,1));
        $publickey_hash=substr($scriptPubkey,3,$hash_length);

        if (substr($scriptPubkey, $hash_length+3) != $this->OP_EQUALVERIFY.$this->OP_CHECKSIG)
        {
            return self::unknownScript();
        }

        $hex_hash=bin2hex($publickey_hash);

        return new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::PAY_TO_HASH_PUBKEY,
            array(BitcoinLib::get_address_from_hash($hex_hash)),
            array()
        );

    }

    /**
     * Parsuje script jako platbu na hash scriptu
     *
     * @param $scriptPubkey
     * @return BitcoinScriptRedeemerDto
     */
    private function parse_as_p2sh($scriptPubkey)
    {
        if ($scriptPubkey[0] != $this->OP_HASH160)
        {
            return $this->unknownScript();
        }

        $length=ord($scriptPubkey[1]);
        $script_hash=bin2hex(substr($scriptPubkey,2,$length));

        if ($scriptPubkey[$length+2] != $this->OP_EQUAL)
        {
            return $this->unknownScript();
        }

        $dto=new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::PAY_TO_SCRIPT_HASH,
            array(BitcoinLib::get_address_from_script($script_hash))
        );

        $dto->setHash($script_hash);
        return $dto;
    }

    /**
     * Parsuje script jako platbu na multisig (více podpisů)
     *
     * @param $scriptPubkey
     * @return BitcoinScriptRedeemerDto
     */
    private function parse_as_multisig($scriptPubkey)
    {
        $required_keys=(int)($scriptPubkey[0] - $this->OP_PUSH_NUMBER_1) + 1;

        if ($required_keys > self::OP_MAX_NUMERIC_VALUE)
        {
            return $this->unknownScript();
        }
        $read_bytes=1;

        $pubkeys=array();
        $addresses=array();
        $key_count=0;

        // in last iteration, $length contains total number of keys
        $length=ord(substr($scriptPubkey,$read_bytes,1));
        $read_bytes++;
        while ($length < ord($this->OP_PUSH_NUMBER_1) || $length > ord($this->OP_NUMBER_END))
        {
            $pubkey=bin2hex(substr($scriptPubkey,$read_bytes,$length));

            $pubkeys[]=$pubkey;
            $addresses[]=BitcoinLib::get_address_from_pubkey($pubkey);

            $read_bytes+=$length;

            $length=ord(substr($scriptPubkey,$read_bytes,1));
            $read_bytes++;

            $key_count++;
        }
        $number_of_keys= ($length - ord($this->OP_PUSH_NUMBER_1)) + 1;

        if ($number_of_keys != $key_count)
        {
            return $this->unknownScript();
        }

        if (substr($scriptPubkey,$read_bytes) != $this->OP_CHECKMULTISIG)
        {
            return $this->unknownScript();
        }

        $dto=new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::PAY_TO_MULTISIG,
            $addresses,
            $pubkeys
        );

        $dto->setMultisigKeyCount($number_of_keys);
        $dto->setMultisigRequiredKeys($required_keys);
        return $dto;
    }

    /**
     * Parsuje nulldata výstup - nepřevádí peníze, jen dává do blockchainu data
     *
     * @param $scriptPubkey
     * @return BitcoinScriptRedeemerDto
     */
    private function parse_as_nulldata($scriptPubkey)
    {
        if ($scriptPubkey[0] != $this->OP_RETURN)
        {
            return $this->unknownScript();
        }

        $data=bin2hex(substr($scriptPubkey,1));

        $dto=new BitcoinScriptRedeemerDto(BitcoinScriptRedeemerDto::PAY_NULLDATA);
        $dto->setData($data);

        return $dto;
    }

    /**
     * Vytvoří DTO pro neznámý script
     * @return BitcoinScriptRedeemerDto - DTO obsahující neznámý script
     */
    private function unknownScript()
    {
        return new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::SCRIPT_UNKNOWN
        );
    }
}