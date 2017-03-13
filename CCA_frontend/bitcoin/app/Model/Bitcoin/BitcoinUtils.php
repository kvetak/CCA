<?php

namespace App\Model\Bitcoin;


use App\Model\Bitcoin\ScriptParser\Dto\BitcoinScriptRedeemerDto;

class BitcoinUtils
{
    /**
     * Serializuje výstup transakce do jednoho stringu, který reprezentuje adresu
     *
     * @param BitcoinScriptRedeemerDto $dto
     */
    public static function serialize_address(BitcoinScriptRedeemerDto $dto)
    {
        switch ($dto->getType())
        {
            case BitcoinScriptRedeemerDto::PAY_TO_PUBKEY:
            case BitcoinScriptRedeemerDto::PAY_TO_HASH_PUBKEY:
            case BitcoinScriptRedeemerDto::PAY_TO_SCRIPT_HASH:
                return $dto->getAddresses()[0];
            case BitcoinScriptRedeemerDto::PAY_TO_MULTISIG:
                return "Multisig :".implode(", ",$dto->getAddresses());
            case BitcoinScriptRedeemerDto::PAY_NULLDATA:
            case BitcoinScriptRedeemerDto::SCRIPT_UNKNOWN:
            default:
                return null;
        }
    }
}