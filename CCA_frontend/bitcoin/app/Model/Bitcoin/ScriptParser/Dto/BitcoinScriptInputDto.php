<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 10.3.17
 * Time: 14:45
 */

namespace App\Model\Bitcoin\ScriptParser\Dto;


class BitcoinScriptInputDto extends AbstractBitcoinScriptDto
{
    /**
     * Public keys present in input
     * @var array
     */
    private $pubkeys;

    /**
     * Signatures present in input
     * @var array
     */
    private $signatures;

    /**
     * In case if pay to scriptHash, there is stored script present in scriptSig
     * @var BitcoinScriptRedeemerDto
     */
    private $pubkeyScript;

    /**
     * BitcoinScriptInputDto constructor.
     */
    public function __construct($type, array $pubkeys=array(), array $signatures=array())
    {
        $this->type=$type;
        $this->pubkeys=$pubkeys;
        $this->signatures=$signatures;
    }

    /**
     * @return array
     */
    public function getPubkeys()
    {
        return $this->pubkeys;
    }

    /**
     * @param array $pubkeys
     */
    public function setPubkeys($pubkeys)
    {
        $this->pubkeys = $pubkeys;
    }

    /**
     * @return array
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * @param array $signatures
     */
    public function setSignatures($signatures)
    {
        $this->signatures = $signatures;
    }

    /**
     * @return BitcoinScriptRedeemerDto
     */
    public function getPubkeyScript()
    {
        return $this->pubkeyScript;
    }

    /**
     * @param BitcoinScriptRedeemerDto $pubkeyScript
     */
    public function setPubkeyScript($pubkeyScript)
    {
        $this->pubkeyScript = $pubkeyScript;
    }
}