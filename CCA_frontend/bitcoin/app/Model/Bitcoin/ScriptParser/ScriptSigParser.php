<?php

namespace App\Model\Bitcoin\ScriptParser;

use App\Model\Bitcoin\ScriptParser\Dto\BitcoinScriptInputDto;
use App\Model\Bitcoin\ScriptParser\Dto\BitcoinScriptRedeemerDto;
use App\Model\Bitcoin\ScriptParser\Dto\ScriptParsingException;
use App\Model\Bitcoin\ScriptParser\Dto\ScriptSignatureDto;

/**
 * Parser for blockchain signature script field
 *
 * Class ScriptSigParser
 * @package App\Model\Bitcoin
 */
class ScriptSigParser extends BaseScriptParser
{
    /**
     * For parsing pay to scriptHash, it's requied script pbukey parser for parsing this script
     * @var ScriptPubkeyParser
     */
    private $scriptPubkeyParser;


    public function __construct(ScriptPubkeyParser $scriptPubkeyParser)
    {
        $this->scriptPubkeyParser=$scriptPubkeyParser;
        parent::__construct();
    }

    /**
     * @param $sig_script
     * @param BitcoinScriptRedeemerDto $redeemerDto
     * @return BitcoinScriptInputDto
     */
    public function parse($sig_script, BitcoinScriptRedeemerDto $redeemerDto)
    {
        try {
            $type = $redeemerDto->getType();
            switch ($type) {
                case BitcoinScriptInputDto::PAY_TO_PUBKEY:
                    return $this->parse_as_p2pk($sig_script);
                case BitcoinScriptInputDto::PAY_TO_HASH_PUBKEY:
                    return $this->parse_as_p2pkh($sig_script);
                case BitcoinScriptInputDto::PAY_TO_MULTISIG:
                    return $this->parse_as_multisig($sig_script);
                case BitcoinScriptInputDto::PAY_TO_SCRIPT_HASH:
                    return $this->parse_as_p2sh($sig_script);
            }
        }
        catch (ScriptParsingException $ex)
        {
        }
        return $this->unknownScript();
    }

    /**
     * Parse sigscript for pay to pubkey method
     *
     * @param $sig_script
     * @return BitcoinScriptInputDto
     */
    private function parse_as_p2pk($sig_script)
    {
        // skip one byte for push to stack number
        $signature= $this->parse_signature($sig_script,1);
        return new BitcoinScriptInputDto(BitcoinScriptInputDto::PAY_TO_PUBKEY,
            array(),
            array($signature)
        );
    }

    /**
     * Parse script as sig script for pay to pubkey hash
     *
     * @param $sig_script
     * @return BitcoinScriptInputDto
     * @throws ScriptParsingException
     */
    private function parse_as_p2pkh($sig_script)
    {
        // skip one byte for push to stack number
        $parsed_length=1;
        $signature = $this->parse_signature($sig_script,$parsed_length,$length);
        $parsed_length += $length;

        $pubkey_length = substr($sig_script, $parsed_length , 1);
        $parsed_length+=1;

        $pubkey = substr($sig_script,$parsed_length, ord($pubkey_length));
        return new BitcoinScriptInputDto(
            BitcoinScriptInputDto::PAY_TO_HASH_PUBKEY,
            array(bin2hex($pubkey)),
            array($signature)
        );
    }

    /**
     * Parse sig script field as pay to multisig transaction
     *
     * @param $sig_script
     * @return BitcoinScriptInputDto
     * @throws ScriptParsingException
     */
    private function parse_as_multisig($sig_script)
    {
        if ($sig_script[0] != $this->OP_FALSE)
        {
            throw new ScriptParsingException();
        }
        $sig_script_length=strlen($sig_script);
        $parsed_length=1;
        $signatures=array();

        while ($parsed_length < $sig_script_length)
        {
            $parsed_length++; // skip byte, indicating how many bytes push on stack (total signature length)
            $length=0;
            $signatures[]=$this->parse_signature($sig_script,$parsed_length,$length);

            $parsed_length+=$length;
        }

        return new BitcoinScriptInputDto(
            BitcoinScriptInputDto::PAY_TO_MULTISIG,
            array(),
            $signatures
        );
   }

    /**
     * Parse sig script as method pay to script hash
     *
     * @param $sig_script
     * @return BitcoinScriptInputDto
     * @throws ScriptParsingException
     */
   private function parse_as_p2sh($sig_script)
   {
        $first_byte=$sig_script[0];
        // it's pay to one key transaction
        if ($first_byte == $this->SIGNATURE_HEADER_INDICATOR)
        {
            $sig_length=0;
            $signature = $this->parse_signature($sig_script,0,$sig_length);

            $parsed_length=$sig_length;
            $pubkey_script=substr($sig_script,$parsed_length);

            $dto=new BitcoinScriptInputDto(
                BitcoinScriptInputDto::PAY_TO_SCRIPT_HASH,
                array(),
                array($signature)
            );
            $dto->setParserPubkeyScript($this->scriptPubkeyParser->parse($pubkey_script));
            $dto->setPubkeyScript($pubkey_script);
            return $dto;
        }
        // it's multisig transaction
        elseif ($first_byte == $this->OP_FALSE)
        {
            $parsed_length=2; // skip OP_FASE and push to stack bytes
            $signatures=array();

            // parse signatures in script
            while ($sig_script[$parsed_length] == $this->SIGNATURE_HEADER_INDICATOR)
            {
                $length=0;
                $signatures[]=$this->parse_signature($sig_script,$parsed_length,$length);

                $parsed_length+=$length;
                $parsed_length++; // skip push to byte of following field
            }

            // rest of script is pubkey script
            $pubkey_script=substr($sig_script,$parsed_length);

            $dto=new BitcoinScriptInputDto(
                BitcoinScriptInputDto::PAY_TO_SCRIPT_HASH,
                array(),
                $signatures
            );
            $dto->setParserPubkeyScript($this->scriptPubkeyParser->parse($pubkey_script));
            $dto->setPubkeyScript($pubkey_script);
            return $dto;
        }
        else
        {
            throw new ScriptParsingException();
        }
   }


    /**
     * Parse one signature in scriptSig
     * @param $script String scriptSig
     * @param $begin int  index - where to begin parsing signature
     * @param int $sig_length return parameter for length of signature
     * @return ScriptSignatureDto
     * @throws ScriptParsingException
     */
    private function parse_signature($script,$begin,&$sig_length=0)
    {
        if($script[$begin] != $this->SIGNATURE_HEADER_INDICATOR)
        {
            throw new ScriptParsingException();
        }

        if ($script[$begin+2] != $this->INTEGER_INDICATOR)
        {
            throw new ScriptParsingException();
        }

        $r_length=ord($script[$begin+3]);
        $r_coord = substr($script,$begin+4,$r_length);

        if ($script[$begin+$r_length+4] != $this->INTEGER_INDICATOR)
        {
            throw new ScriptParsingException();
        }

        $s_length=ord($script[$begin+$r_length+5]);
        $s_coord = substr($script, $begin + $r_length+  6, $s_length);

        $sig_type=bin2hex(substr($script, $begin + $s_length + $r_length + 6,1));
        $signature = new ScriptSignatureDto(bin2hex($r_coord) , bin2hex($s_coord),$sig_type);

        $sig_length=$s_length + $r_length + 7;
        return $signature;
    }


    /**
     * Vytvoří DTO pro neznámý script
     * @return BitcoinScriptInputDto - DTO obsahující neznámý script
     */
    private function unknownScript()
    {
        return new BitcoinScriptInputDto(
            BitcoinScriptInputDto::SCRIPT_UNKNOWN
        );
    }
}