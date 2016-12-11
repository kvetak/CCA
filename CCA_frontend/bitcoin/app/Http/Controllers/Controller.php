<?php

namespace App\Http\Controllers;
use Illuminate\Pagination\BootstrapThreeNextPreviousButtonRendererTrait;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Pagination\UrlWindowPresenterTrait;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\HtmlString;

class Controller extends BaseController
{
    use BootstrapThreeNextPreviousButtonRendererTrait, UrlWindowPresenterTrait;

    /**
     * Instancia Strankovaca.
     *
     * @var
     */
    protected $paginator;

    /**
     * Nastavenie strankovaca.
     * @param mixed $paginator
     */
    public function setPaginator($paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Ziskanie objektu strankovaca.
     * @return mixed
     */
    public function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * Kontrola ci strankovac ma nejake stranky.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->paginator->hasPages();
    }

    /**
     * Vytvorenie HTML strankovaca v Bootstrap HTML.
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function renderPagination($simple = False)
    {
        $this->window = UrlWindow::make($this->paginator);
        if ($this->hasPages()) {
            if($simple){
                return new HtmlString(sprintf(
                    '<ul class="pagination">%s %s</ul>',
                    $this->getPreviousButton("<< Previous"),
                    $this->getNextButton("Next >>")
                ));
            }else{
                return new HtmlString(sprintf(
                    '<ul class="pagination">%s %s %s</ul>',
                    $this->getPreviousButton(),
                    $this->getLinks(),
                    $this->getNextButton()
                ));
            }
        }
        return '';
    }

    /**
     * Get HTML wrapper for an available page link.
     *
     * @param  string  $url
     * @param  int  $page
     * @param  string|null  $rel
     * @return string
     */
    protected function getAvailablePageWrapper($url, $page, $rel = null)
    {
        $rel = is_null($rel) ? '' : ' rel="'.$rel.'"';

        return '<li><a href="'.htmlentities($url).'"'.$rel.'>'.$page.'</a></li>';
    }

    /**
     * Get HTML wrapper for disabled text.
     *
     * @param  string  $text
     * @return string
     */
    protected function getDisabledTextWrapper($text)
    {
        return '<li class="disabled"><span>'.$text.'</span></li>';
    }

    /**
     * Get HTML wrapper for active text.
     *
     * @param  string  $text
     * @return string
     */
    protected function getActivePageWrapper($text)
    {
        return '<li class="active"><span>'.$text.'</span></li>';
    }

    /**
     * Get a pagination "dot" element.
     *
     * @return string
     */
    protected function getDots()
    {
        return $this->getDisabledTextWrapper('...');
    }

    /**
     * Get the current page from the paginator.
     *
     * @return int
     */
    protected function currentPage()
    {
        return $this->paginator->currentPage();
    }

    /**
     * Get the last page from the paginator.
     *
     * @return int
     */
    protected function lastPage()
    {
        return $this->paginator->lastPage();
    }
}
