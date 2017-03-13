<?php

namespace App\Model\Bitcoin;


use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\NumberTheory;


/**
 * Method implementation from https://github.com/Bit-Wasp/bitcoin-lib-php/blob/master/src/BitcoinLib.php
 *
 * Class BitcoinLib
 * @package App\Model\Bitcoin
 */
class BitcoinLib
{
    /**
     * Compress Public Key
     *
     * Converts an uncompressed public key to the shorter format. These
     * compressed public key's have a prefix of 02 or 03, indicating whether
     * Y is odd or even (tested by gmp_mod2(). With this information, and
     * the X coordinate, it is possible to regenerate the uncompressed key
     * at a later stage.
     *
     * @param    string $public_key
     * @return    string
     */
    public static function compress_public_key($public_key)
    {
        $math = EccFactory::getAdapter();
        $x_hex = substr($public_key, 2, 64);
        $y = $math->hexDec(substr($public_key, 66, 64));
        $parity = $math->mod(gmp_init($y,10), gmp_init(2,10));
        return (($parity == 0) ? '02' : '03') . $x_hex;
    }


    /**
     * Decompress Public Key
     *
     * Accepts a y_byte, 02 or 03 indicating whether the Y coordinate is
     * odd or even, and $passpoint, which is simply a hexadecimal X coordinate.
     * Using this data, it is possible to deconstruct the original
     * uncompressed public key.
     *
     * @param $key
     * @return array|bool
     */
    public static function decompress_public_key($key)
    {
        $math = EccFactory::getAdapter();
        $y_byte = substr($key, 0, 2);
        $x_coordinate = substr($key, 2);
        $x = self::hex_decode($x_coordinate);
        $theory = new NumberTheory($math);
        $generator = EccFactory::getSecgCurves($math)->generator256k1();
        $curve = $generator->getCurve();
        try {
            $x3 = $math->powmod(gmp_init($x,10), gmp_init(3,10), $curve->getPrime());
            $y2 = $math->add($x3, $curve->getB());
            $y0 = $theory->squareRootModP($y2, $curve->getPrime());
            if ($y0 == null) {
                throw new \InvalidArgumentException("Invalid public key");
            }
            $y1 = $math->sub($curve->getPrime(), $y0);

            $y = ($y_byte == '02')
                ? (($math->mod($y0, gmp_init(2,10)) == '0') ? $y0 : $y1)
                : (($math->mod($y0, gmp_init(2,10)) != '0') ? $y0 : $y1);

            // Convert the y coordinate to hex, and pad it to 64 characters.
            $y_coordinate = str_pad($math->decHex(strval($y)), 64, '0', STR_PAD_LEFT);
            $point = $curve->getPoint(gmp_init($x,10), $y);
        } catch (\Exception $e) {
            throw $e;
//            throw new \InvalidArgumentException("Invalid public key");
        }
        return array(
            'x' => $x_coordinate,
            'y' => $y_coordinate,
            'point' => $point,
            'public_key' => '04' . $x_coordinate . $y_coordinate
        );
    }

    /**
     * Hex Decode
     *
     * Decodes a hexadecimal $hex string into a decimal number.
     *
     * @param    string $hex
     * @return    int
     */
    public static function hex_decode($hex)
    {
        return gmp_strval(gmp_init($hex, 16), 10);
    }

    /**
     * Hex Encode
     *
     * Encodes a decimal $number into a hexadecimal string.
     *
     * @param    int $number
     * @return    string
     */
    public static function hex_encode($number)
    {
        $hex = gmp_strval(gmp_init($number, 10), 16);
        return (strlen($hex) % 2 != 0) ? '0' . $hex : $hex;
    }


    /**
     * Get New Private Key
     *
     * This function generates a new private key, a number from 1 to $n.
     * Once it finds an acceptable value, it will encode it in hex, pad it,
     * and return the private key.
     *
     * @return    string
     */
    public static function get_new_private_key()
    {
        $math = EccFactory::getAdapter();
        $g = EccFactory::getSecgCurves($math)->generator256k1();
        $privKey = gmp_strval(gmp_init(bin2hex(self::get_random()), 16));
        while ($math->cmp(gmp_init($privKey,10), $g->getOrder()) !== -1) {
            $privKey = gmp_strval(gmp_init(bin2hex(self::get_random()), 16));
        }
        $privKeyHex = $math->dechex($privKey);
        return str_pad($privKeyHex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a 32 byte string of random data.
     *
     * This function can be overridden if you have a more sophisticated
     * random number generator, such as a hardware based random number
     * generator, or a system capable of delivering lot's of entropy for
     * MCRYPT_DEV_RANDOM. Do not override this if you do not know what
     * you are doing!
     *
     * @return string
     */
    protected static function get_random()
    {
        $random="";
        $length=32;
        for ($i=0 ; $i < $length ; $i++)
        {
            $random.=chr(rand(0,255));
        }

        return $random;
    }

    /**
     * Private Key To Public Key
     *
     * Accepts a $privKey as input, and does EC multiplication to obtain
     * a new point along the curve. The X and Y coordinates are the public
     * key, which are returned as a hexadecimal string in uncompressed
     * format.
     *
     * @param    string  $privKey
     * @param    boolean $compressed
     * @return    string
     */
    public static function private_key_to_public_key($privKey, $compressed = false)
    {
        $math = EccFactory::getAdapter();
        $g = EccFactory::getSecgCurves($math)->generator256k1();
        $privKey = self::hex_decode($privKey);
        $secretG = $g->mul(gmp_init($privKey,10));
        $xHex = self::hex_encode(strval($secretG->getX()));
        $yHex = self::hex_encode(strval($secretG->getY()));
        $xHex = str_pad($xHex, 64, '0', STR_PAD_LEFT);
        $yHex = str_pad($yHex, 64, '0', STR_PAD_LEFT);
        $public_key = '04' . $xHex . $yHex;
        return ($compressed == true) ? self::compress_public_key($public_key) : $public_key;
    }
}