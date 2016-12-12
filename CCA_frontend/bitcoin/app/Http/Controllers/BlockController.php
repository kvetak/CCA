<?php
namespace App\Http\Controllers;

use App\Model\Bitcoin\BitcoinBlockModel;

use App\Model\Bitcoin\BitcoinTransactionModel;
use App\Model\CurrencyType;
use App\Model\Litecoin\LitecoinTransactionModel;
use Hamcrest\Text\StringEndsWith;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\BootstrapThreeNextPreviousButtonRendererTrait;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HtmlString;

/**
 * Radič pre prácu s blokmi.
 *
 * Class BlockController
 * @package App\Http\Controllers
 *
 * @author Tomas Drozda <tomas.drozda@icloud.com>
 */
class BlockController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, BootstrapThreeNextPreviousButtonRendererTrait;
    const LIMIT_PER_PAGE        = 25;
    const TRANSACTIONS_PER_PAGE = 50;

    /**
     * Zobrazenie profilovej stranky bloku.
     * @param $hash
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findOne($currency, $hash)
    {
        $displayOnlyHeader      = True;
        $blockModelClass        = CurrencyType::blockModel($currency);
        $transactionModelClass  = CurrencyType::transactionModel($currency);
        $block          = new $blockModelClass($hash);

        $lastBlock                  = $block->getLastBlock();
        $isBlockConfirmed           = $blockModelClass::isConfirmed($block['height'], $lastBlock['height']);
        $blockConfirmationMessage   = $isBlockConfirmed ? 'Transactions in block are confirmed!' : 'Transactions in block are not confirmed!';
        $confirmations              = $lastBlock['height'] - $block['height'];
        $pagination = new LengthAwarePaginator([], (int)$block['transactions'], self::TRANSACTIONS_PER_PAGE);
        $skip = ($pagination->currentPage() - 1) * self::TRANSACTIONS_PER_PAGE;
        $transactions               = $transactionModelClass::findByBlockHash($hash, self::TRANSACTIONS_PER_PAGE, $skip);
        $pagination->setPath(route('block_findone',['hash'=>$block['hash'], 'currency' => 'bitcoin']));

        view()->composer('transaction.transactionListItem', function($view) use($currency) {
            $view->with('currency', $currency);
        });

        return view('/block/findOne', compact('block', 'transactions','displayOnlyHeader', 'isBlockConfirmed', 'blockConfirmationMessage', 'confirmations', 'pagination', 'currency'));
    }

    /**
     * Zobrazenie vypisu blokov blockchainu.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findAll($currency = "bitcoin")
    {
        $blockModelClass = CurrencyType::blockModel($currency);
        $blockM          = new $blockModelClass();
        $total      = $blockM->getCount();
        $this->setPaginator(new LengthAwarePaginator([], $total,self::LIMIT_PER_PAGE));
        $this->paginator->setPath('block');
        $skip       = ($this->currentPage() - 1) * self::LIMIT_PER_PAGE;
        $blocks     = $blockM->findAll(self::LIMIT_PER_PAGE, $skip, [
            'height'            => true,
            'hash'              => true,
            'time'              => true,
            'transactions'      => true,
            'sum_of_outputs'    => true
        ]);
        $pagination = $this->renderPagination(True);
        return view('block/findAll', compact('blocks', 'pagination', 'total', 'currency'));
    }
}
