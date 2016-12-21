<?php
/**
 * Author: Marti Očenáš - xocena04
 * Date: 20.11.16
 * Time: 15:06
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class TypeReader
 * @package BlockChainParser
 *
 * Class for reading specific data types from blockchain.
 *
 * Can read data types such as Integer, Hash, Compact size Integer etc.
 */
class TypeReader
{
    /**
     * @var FileReader - handler of input file, which can be read from
     */
    private $fileReader;


    /**
     * @var Boolean - determine if system if little or big endian
     */
    private $littleEndian;

    /**
     * TypeReader constructor.
     * @param FileReader $fileReader - instance of file reader, initiated for reading blockchain
     */
    public function __construct(FileReader $fileReader)
    {
        $this->fileReader=$fileReader;
        $this->littleEndian=$this->isLittleEndian();
    }

    /**
     * @brief Read 32b integer from input file
     * @return Integer - value of integer
     */
    public function readInt()
    {
        return unpack("l",$this->fileReader->readBytes(4))[1];
    }

    /**
     * @brief Read 256b hash from input file
     */
    public function readHash()
    {
        $value=$this->fileReader->readBytes(32);
        return $this->swapEndianness($value);
    }

    /**
     * @brief Read 32b unsigned integer from input file
     *
     * Always reads as big endian
     */
    public function readUInt()
    {
        return unpack("L",$this->fileReader->readBytes(4))[1];
    }

    /**
     * @brief Read variable length integer (compact size integer) 1-9B from input file
     */
    public function readVarInt()
    {
        $first_byte=unpack("C",$this->fileReader->readBytes(1))[1];
        if ($first_byte <= 252)
        {
            return $first_byte;
        }
        $length=0;
        switch ($first_byte)
        {
            case 253:
                $length=2;
                break;
            case 254:
                $length=4;
                break;
            case 255:
                $length=8;
                break;
        }
        return $this->decode_int($this->fileReader->readBytes($length),$length*8);
    }

    /**
     * @brief Read 64b integer from input file
     */
    public function read64Int()
    {
        return unpack("q",$this->fileReader->readBytes(8))[1];
    }//        return unpack("N",$this->fileReader->readBytes(4))[1];



    /**
     * @brief Read bitcoin script field
     * @param $length - length of script
     *
     * @return String - script value
     */
    public function readCScript($length)
    {
        return $this->fileReader->readBytes($length);
    }


    /**
     * @brief Determine if system is little endian or not
     * @return bool - true if system is little endian, false otherwise
     */
    private function isLittleEndian() {
        return unpack('S',"\x01\x00")[1] === 1;
    }

    /**
     * @brief Swap order of bytes in string
     *
     * @param String $string - value to be swapped
     * @return string - swapped value
     */
    private function swapEndianness($string)
    {
        return implode('', array_reverse(str_split($string, 1)));
    }


    private function decode_int($data, $bits=false)
    {
        if ($bits === false) $bits = strlen($data) * 8;
        if ($bits <= 0) return false;
        switch ($bits) {
            case 8:
                $return = unpack('C', $data);
                $return = $return[1];
                break;

            case 16:
                $return = unpack('v', $data);
                $return = $return[1];
                break;

            case 24:
                $return = unpack('ca/ab/cc', $data);
                $return = $return['a'] + ($return['b'] << 8) + ($return['c'] << 16);
                break;

            case 32:
                $return = unpack('V', $data);
                $return = $return[1];
                break;

            case 48:
                $return = unpack('va/vb/vc', $data);
                $return = $return['a'] + ($return['b'] << 16) + ($return['c'] << 32);
                break;

            case 64:
                $return = unpack('Va/Vb', $data);
                $return = $return['a'] + ($return['b'] << 32);
                break;
        }
        return $return;
    }
}