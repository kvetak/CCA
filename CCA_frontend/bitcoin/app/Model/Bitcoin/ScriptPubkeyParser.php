<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 2.3.17
 * Time: 15:08
 */

namespace App\Model\Bitcoin;

use App\Model\Base58Encoder;
use App\Model\Bitcoin\Dto\BitcoinScriptRedeemerDto;

/**
 *
 *
 * Class ScriptPubkeyParser
 * @package App\Model\Bitcoin
 */
class ScriptPubkeyParser
{
    const OP_MAX_NUMERIC_VALUE=16;

    // "konstanty" pro bytecode jednotlivých operací
    private $OP_PUSH_65B,
        $OP_DUP,
        $OP_HASH160,
        $OP_RETURN,
        $OP_NUMBER_START,
        $OP_NUMBER_END,
        $OP_CHECKSIG,
        $OP_CHECKMULTISIG,
        $OP_EQUAL,
        $OP_EQUALVERIFY;


    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        // bytecode pro jednotlivé operace
        $this->OP_PUSH_65B=chr(0x41);
        $this->OP_DUP=chr(0x76);
        $this->OP_HASH160=chr(0xa9);
        $this->OP_RETURN=chr(0x6a);

        $this->OP_NUMBER_START=chr(0x52);
        $this->OP_NUMBER_END=chr(0x60);
        $this->OP_CHECKSIG=chr(0xac);
        $this->OP_CHECKMULTISIG=chr(0xae);
        $this->OP_EQUAL=chr(0x87);
        $this->OP_EQUALVERIFY=chr(0x88);
    }

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
        if ($first_byte >= $this->OP_NUMBER_START and $first_byte <= $this->OP_NUMBER_END)
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
     * Vytvoří DTO pro neznámý script
     * @return BitcoinScriptRedeemerDto - DTO obsahující neznámý script
     */
    private function unknownScript()
    {
        return new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::SCRIPT_UNKNOWN
        );
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
            array($this->get_address_from_pubkey($hex_publickey)),
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
            array($this->get_address_from_hash($hex_hash)),
            array($hex_hash)
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

//        echo "address: ".$this->get_address_from_script($script_hash).PHP_EOL;

        $dto=new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::PAY_TO_SCRIPT_HASH,
            array($this->get_address_from_script($script_hash))
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
        $required_keys=(int)($scriptPubkey[0] - $this->OP_NUMBER_START) + 2;

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
        while ($length < ord($this->OP_NUMBER_START) || $length > ord($this->OP_NUMBER_END))
        {
            $pubkey=bin2hex(substr($scriptPubkey,$read_bytes,$length));

            $pubkeys[]=$pubkey;
            $addresses[]=$this->get_address_from_pubkey($pubkey);

            $read_bytes+=$length;

            $length=ord(substr($scriptPubkey,$read_bytes,1));
            $read_bytes++;

            $key_count++;
        }
        $number_of_keys= ($length - ord($this->OP_NUMBER_START)) + 2;

        if ($number_of_keys != $key_count)
        {
            return $this->unknownScript();
        }

        if (substr($scriptPubkey,$read_bytes) != $this->OP_CHECKMULTISIG)
        {
            return $this->unknownScript();
        }

        $dto=new BitcoinScriptRedeemerDto(
            BitcoinScriptRedeemerDto::PAY_TO_MILTISIG,
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
     * Vypočte bitcoin adresu z veřejného klíče
     * @param $public_key String - hexadecimální reprezentace veřejného klíče
     * @return string BTC adresa
     */
    private function get_address_from_pubkey($public_key)
    {
        $SHA_HASH = hash("sha256",hex2bin($public_key));
        $RIPEMD_HASH = hash("ripemd160",hex2bin($SHA_HASH));

        return $this->get_address_from_hash($RIPEMD_HASH);
    }

    /**
     * @param $hash String - hexadecimální reprezentace RIPEMD160 hashe veřejného klíče
     * @return string BTC adresa
     */
    private function get_address_from_hash($hash)
    {
        $HASH_VER = "00".$hash; // add version before hash

        $SHA_NEXT_HASH = hash("sha256",hex2bin($HASH_VER));
        $SHA_NEXT_HASH2 = hash("sha256",hex2bin($SHA_NEXT_HASH));

        $checksum = substr($SHA_NEXT_HASH2,0,8);

        $binary_addr = $HASH_VER . $checksum;

        return "1".Base58Encoder::bc_base58_encode(Base58Encoder::bc_hexdec($binary_addr)).PHP_EOL;
    }

    /**
     * Výpočet adresy pro scripthash metodu
     * @param $script_hash String - hash scriptu
     * @return string BTC adresa
     */
    private function get_address_from_script($script_hash)
    {
        $HASH_VER = "05".$script_hash; // add version before hash

        $SHA_NEXT_HASH = hash("sha256",hex2bin($HASH_VER));
        $SHA_NEXT_HASH2 = hash("sha256",hex2bin($SHA_NEXT_HASH));

        $checksum = substr($SHA_NEXT_HASH2,0,8);

        $binary_addr = $HASH_VER . $checksum;

        /**
         * správně by se před výsledek mělo přidat číslo "3", ale z neznámého důvodu se tam toto číslo přidává
         * již při výpočtu base58_encode
         */
        return Base58Encoder::bc_base58_encode(Base58Encoder::bc_hexdec($binary_addr)).PHP_EOL;
    }
}