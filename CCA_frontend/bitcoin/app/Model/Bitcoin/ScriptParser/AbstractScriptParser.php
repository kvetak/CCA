<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 10.3.17
 * Time: 14:30
 */

namespace App\Model\Bitcoin\ScriptParser;

use App\Model\Base58Encoder;

/**
 * Base class for parsing blockchain script fields
 *
 * Class AbstractScriptParser
 * @package App\Model\Bitcoin
 */
abstract class AbstractScriptParser
{
    const OP_MAX_NUMERIC_VALUE=16;

    // "konstanty" pro bytecode jednotlivých operací
    protected $OP_PUSH_65B,
        $OP_DUP,
        $OP_HASH160,
        $OP_RETURN,
        $OP_NUMBER_START,
        $OP_NUMBER_END,
        $OP_CHECKSIG,
        $OP_CHECKMULTISIG,
        $OP_EQUAL,
        $OP_EQUALVERIFY;

    protected $SIGNATURE_HEADER_INDICATOR,
        $INTEGER_INDICATOR,
        $SCRIPT_SIG_SEPARATOR,
        $OP_FALSE;

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

        $this->OP_NUMBER_START=chr(0x51);
        $this->OP_NUMBER_END=chr(0x60);
        $this->OP_CHECKSIG=chr(0xac);
        $this->OP_CHECKMULTISIG=chr(0xae);
        $this->OP_EQUAL=chr(0x87);
        $this->OP_EQUALVERIFY=chr(0x88);

        $this->SIGNATURE_HEADER_INDICATOR=chr(0x30);
        $this->INTEGER_INDICATOR=chr(0x02);
        $this->SCRIPT_SIG_SEPARATOR=chr(0x01);
        $this->OP_FALSE=chr(0x00);
    }


    /**
     * Vypočte bitcoin adresu z veřejného klíče
     * @param $public_key String - hexadecimální reprezentace veřejného klíče
     * @return string BTC adresa
     */
    protected function get_address_from_pubkey($public_key)
    {
        return $this->get_address_from_hash($this->ripemd160_hash($public_key));
    }

    /**
     * Vypočte čast hashe veřejného klíče po provedení ripemd160
     *
     * @param $public_key string hex
     * @return string bin
     */
    protected function ripemd160_hash($public_key)
    {
        $SHA_HASH = hash("sha256",hex2bin($public_key));
        $RIPEMD_HASH = hash("ripemd160",hex2bin($SHA_HASH));

        return $RIPEMD_HASH;
    }

    /**
     * @param $hash String - hexadecimální reprezentace RIPEMD160 hashe veřejného klíče
     * @return string BTC adresa
     */
    protected function get_address_from_hash($hash)
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
    protected function get_address_from_script($script_hash)
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