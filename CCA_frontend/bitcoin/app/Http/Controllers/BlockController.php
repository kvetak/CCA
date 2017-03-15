<?php
namespace App\Http\Controllers;

use App\Model\CurrencyType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\BootstrapThreeNextPreviousButtonRendererTrait;
use Illuminate\Pagination\LengthAwarePaginator;

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
    const LIMIT_PER_PAGE        = 50;
    const TRANSACTIONS_PER_PAGE = 50;

    /**
     * Zobrazenie profilovej stranky bloku.
     * @param $hash
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function findOne($currency, $hash)
    {
        $displayOnlyHeader      = True;
        $blockModel       = CurrencyType::blockModel($currency);
        $transactionModelClass  = CurrencyType::transactionModel($currency);

        $block= $blockModel->findByHash($hash);

        $lastBlock                  = $blockModel->getLastBlock();
        $isBlockConfirmed           = $blockModel->isConfirmed($block->getHeight(), $lastBlock->getHeight());
        $blockConfirmationMessage   = $isBlockConfirmed ? 'Transactions in block are confirmed!' : 'Transactions in block are not confirmed!';
        $confirmations              = $lastBlock->getHeight() - $block->getHeight();
        $pagination = new LengthAwarePaginator([], $block->getTransactionsCount(), self::TRANSACTIONS_PER_PAGE);
        $skip = ($pagination->currentPage() - 1) * self::TRANSACTIONS_PER_PAGE;
        $transactions               = $transactionModelClass->findByBlockHash($hash, self::TRANSACTIONS_PER_PAGE, $skip);
        $pagination->setPath(route('block_findone',['hash'=>$block->getHash(), 'currency' => 'bitcoin']));

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
        $blockModel = CurrencyType::blockModel($currency);
        $total      = $blockModel->getCount();
        $this->setPaginator(new LengthAwarePaginator([], $total,self::LIMIT_PER_PAGE));
        $this->paginator->setPath('block');
        $skip       = ($this->currentPage() - 1) * self::LIMIT_PER_PAGE;
        $blocks     = $blockModel->findAll(self::LIMIT_PER_PAGE, $skip);
        $pagination = $this->renderPagination(True);
        return view('block/findAll', compact('blocks', 'pagination', 'total', 'currency'));
    }
}
