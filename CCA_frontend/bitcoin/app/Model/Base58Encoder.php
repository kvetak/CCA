<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 27.2.17
 * Time: 16:26
 */

namespace App\Model;


use Exception;

abstract class Base58Encoder
{
// BCmath version for huge numbers
    private static function bc_arb_encode($num, $basestr) {
        $base = strlen($basestr);
        $rep = '';

        while( strlen($num) >= 2  || intval($num) > 0 ){
            $rem = bcmod($num, $base);
            $rep = $basestr[intval($rem)] . $rep;
            $num = bcdiv(bcsub($num, $rem), $base);
        }
        return $rep;
    }

    private static function bc_arb_decode($num, $basestr) {
        $base = strlen($basestr);
        $dec = '0';

        $num_arr = str_split((string)$num);
        $cnt = strlen($num);
        for($i=0; $i < $cnt; $i++) {
            $pos = strpos($basestr, $num_arr[$i]);
            if( $pos === false ) {
                Throw new Exception(sprintf('Unknown character %s at offset %d', $num_arr[$i], $i));
            }
            $dec = bcadd(bcmul($dec, $base), $pos);
        }
        return $dec;
    }


    // base 58 alias
    public static function bc_base58_encode($num) {
        return self::bc_arb_encode($num, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
    }
    public static function bc_base58_decode($num) {
        return self::bc_arb_decode($num, '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
    }

    //hexdec with BCmath
    public static function bc_hexdec($num) {
        return self::bc_arb_decode(strtolower($num), '0123456789abcdef');
    }
    public static function bc_dechex($num) {
        return self::bc_arb_encode($num, '0123456789abcdef');
    }
}