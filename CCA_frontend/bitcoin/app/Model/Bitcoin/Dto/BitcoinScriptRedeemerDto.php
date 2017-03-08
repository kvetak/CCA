<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 2.3.17
 * Time: 15:17
 */

namespace App\Model\Bitcoin\Dto;

/**
 * Class BitcoinScriptRedeemerDto
 * @package App\Model\Bitcoin\Dto
 *
 * @author Martin Očenáš - xocena04
 *
 * DTO pro přenos informací získaných z políčka ScriptPubkey ve výstupu transakce
 */
class BitcoinScriptRedeemerDto
{
    /**
     * Basic transaction pubkey script types
     */
    const PAY_TO_PUBKEY=0,
        PAY_TO_HASH_PUBKEY=1,
        PAY_TO_SCRIPT_HASH=2,
        PAY_TO_MILTISIG=3,
        PAY_NULLDATA=4,
        SCRIPT_UNKNOWN=5;

    /**
     * Type of transaction script
     * @var
     */
    private $type;

    /**
     * Array of addresses of possible redeemers of payment
     * @var array
     */
    private $addresses;

    /**
     * Array of public keys or their hashes, that is present in blockchain
     * @var array
     */
    private $pubkeys;

    /**
     * Other data present in transaction
     * @var string
     */
    private $data;

    /**
     * Hash of P2SH transaction
     * @var hash
     */
    private $hash;

    /**
     * Number of keys in multisig, required to redeem transaction
     * @var int
     */
    private $multisig_required_keys;

    /**
     * Total number of keys in multisig transaction
     * @var int
     */
    private $multisig_key_count;

    /**
     * BitcoinScriptRedeemerDto constructor.
     * @param $type
     * @param array $addresses
     * @param array $pubkeys
     */
    public function __construct($type, array $addresses=array(), array $pubkeys=array())
    {
        $this->type = $type;
        $this->addresses = $addresses;
        $this->pubkeys = $pubkeys;
    }


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param array $addresses
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
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
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return hash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param hash $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return int
     */
    public function getMultisigRequiredKeys()
    {
        return $this->multisig_required_keys;
    }

    /**
     * @param int $multisig_required_keys
     */
    public function setMultisigRequiredKeys($multisig_required_keys)
    {
        $this->multisig_required_keys = $multisig_required_keys;
    }

    /**
     * @return int
     */
    public function getMultisigKeyCount()
    {
        return $this->multisig_key_count;
    }

    /**
     * @param int $multisig_key_count
     */
    public function setMultisigKeyCount($multisig_key_count)
    {
        $this->multisig_key_count = $multisig_key_count;
    }

}