<?php

namespace App\Http\Controllers;
use App\Model\Bitcoin\BitcoinLib;
use App\Model\CurrencyType;

/**
 * Kontroler pro zobrazování veřejných klíčů
 *
 * Class PubkeyController
 * @package App\Http\Controllers
 *
 * @author Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */
class PubkeyController extends Controller
{

    /**
     * Zobrazení jednoho klíče
     *
     * @param string $currency
     * @param string $pubkey komprimovaný veřejný klíč
     */
    public function findOne($currency, $pubkey)
    {
        $pubkeyModel    = CurrencyType::pubkeyModel($currency);
        $pubkeyDto      = $pubkeyModel->findByCompressesPublicKey($pubkey);

        $uncompressesPubkey = BitcoinLib::decompress_public_key($pubkey)['public_key'];

        $addresses      = $pubkeyModel->getAddressesForPubkey($pubkeyDto);
        return view('/pubkey/findOne', compact('currency', 'pubkeyDto', 'uncompressesPubkey', 'addresses'));
    }
}