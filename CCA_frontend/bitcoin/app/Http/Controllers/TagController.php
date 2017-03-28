<?php

namespace App\Http\Controllers;
use App\Model\CurrencyType;

/**
 * Class TagController
 * @package App\Http\Controllers
 *
 * Controller pro práci s tagy
 * @author Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */
class TagController extends Controller
{
    /**
     * Zobrazení jednoho tagu
     * @param $tagId integer ID tagu který se má zobrazit
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findOne($currency, $tag)
    {
        $tagModel = CurrencyType::tagModel($currency);
        $tagDto=$tagModel->findById($tag);
        $addresses = $tagModel->findAddressesWithTag($tagDto);

        return view('tags/findOne',compact('tagDto','currency','addresses'));
    }
}